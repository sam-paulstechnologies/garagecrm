<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InboxController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function index(Request $request)
    {
        return $this->renderInbox($request);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        return $this->renderInbox($request, $lead);
    }

    public function reply(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $message = trim($validated['message']);

        if ($message === '') {
            return back()->withErrors([
                'message' => 'Please enter a reply message.',
            ]);
        }

        $this->storeMessageLog($lead, $message);

        $this->touchLeadAfterManagerReply($lead);

        return redirect()
            ->route('manager.inbox.show', $lead)
            ->with('success', 'Reply saved successfully. WhatsApp sending can now be connected to the unified messaging service.');
    }

    public function resumeBot(Lead $lead)
    {
        $this->authorizeLead($lead);

        if (Schema::hasColumn('leads', 'bot_paused')) {
            $lead->bot_paused = false;
        }

        if (Schema::hasColumn('leads', 'manager_takeover')) {
            $lead->manager_takeover = false;
        }

        if (Schema::hasColumn('leads', 'assigned_to_manager')) {
            $lead->assigned_to_manager = false;
        }

        if (Schema::hasColumn('leads', 'escalated_at')) {
            $lead->escalated_at = null;
        }

        $lead->save();

        return redirect()
            ->route('manager.inbox.show', $lead)
            ->with('success', 'Bot resumed for this conversation.');
    }

    protected function renderInbox(Request $request, ?Lead $selectedLead = null)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        $leads = Lead::query()
            ->where('company_id', $companyId)
            ->when(Schema::hasColumn('leads', 'is_active'), function ($query) {
                $query->where('is_active', 1);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    foreach ([
                        'name',
                        'full_name',
                        'customer_name',
                        'phone',
                        'mobile',
                        'phone_number',
                        'whatsapp_number',
                        'email',
                        'vehicle_make',
                        'vehicle_model',
                        'notes',
                    ] as $column) {
                        if (Schema::hasColumn('leads', $column)) {
                            $sub->orWhere($column, 'like', '%' . $q . '%');
                        }
                    }
                });
            })
            ->when($status !== '' && Schema::hasColumn('leads', 'status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(Schema::hasColumn('leads', 'status'), function ($query) {
                $query->whereNotIn('status', [
                    'Disqualified',
                    'Closed',
                    'Converted',
                    'Converted to Opportunity',
                ]);
            })
            ->when(Schema::hasColumn('leads', 'manager_takeover'), function ($query) {
                $query->orderByDesc('manager_takeover');
            })
            ->when(Schema::hasColumn('leads', 'escalated_at'), function ($query) {
                $query->orderByDesc('escalated_at');
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        if (! $selectedLead && $leads->count()) {
            $selectedLead = $leads->first();
        }

        $messages = $selectedLead
            ? $this->messagesForLead($selectedLead)
            : collect();

        return view('manager.inbox.index', compact(
            'leads',
            'selectedLead',
            'messages',
            'q',
            'status'
        ));
    }

    protected function authorizeLead(Lead $lead): void
    {
        abort_if((int) $lead->company_id !== $this->companyId(), 403);
    }

    protected function messagesForLead(Lead $lead)
    {
        if (! Schema::hasTable('message_logs')) {
            return collect();
        }

        $query = DB::table('message_logs');

        if (Schema::hasColumn('message_logs', 'company_id')) {
            $query->where('company_id', $this->companyId());
        }

        if (Schema::hasColumn('message_logs', 'lead_id')) {
            $query->where('lead_id', $lead->id);
        } elseif (Schema::hasColumn('message_logs', 'conversation_id') && ! empty($lead->conversation_id)) {
            $query->where('conversation_id', $lead->conversation_id);
        } else {
            return collect();
        }

        return $query
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();
    }

    protected function storeMessageLog(Lead $lead, string $message): void
    {
        if (! Schema::hasTable('message_logs')) {
            return;
        }

        $data = [];

        $this->putIfColumnExists($data, 'message_logs', 'company_id', $this->companyId());
        $this->putIfColumnExists($data, 'message_logs', 'lead_id', $lead->id);

        if (! empty($lead->conversation_id)) {
            $this->putIfColumnExists($data, 'message_logs', 'conversation_id', $lead->conversation_id);
        }

        $this->putIfColumnExists($data, 'message_logs', 'channel', 'whatsapp');
        $this->putIfColumnExists($data, 'message_logs', 'direction', 'outbound');
        $this->putIfColumnExists($data, 'message_logs', 'message_type', 'text');
        $this->putIfColumnExists($data, 'message_logs', 'type', 'manager_reply');
        $this->putIfColumnExists($data, 'message_logs', 'status', 'created');
        $this->putIfColumnExists($data, 'message_logs', 'provider', 'meta');
        $this->putIfColumnExists($data, 'message_logs', 'sent_by', auth()->id());
        $this->putIfColumnExists($data, 'message_logs', 'created_by', auth()->id());

        foreach (['content', 'message', 'body', 'text'] as $column) {
            if (Schema::hasColumn('message_logs', $column)) {
                $data[$column] = $message;
                break;
            }
        }

        if (Schema::hasColumn('message_logs', 'created_at')) {
            $data['created_at'] = now();
        }

        if (Schema::hasColumn('message_logs', 'updated_at')) {
            $data['updated_at'] = now();
        }

        if (! empty($data)) {
            DB::table('message_logs')->insert($data);
        }
    }

    protected function touchLeadAfterManagerReply(Lead $lead): void
    {
        if (Schema::hasColumn('leads', 'last_contacted_at')) {
            $lead->last_contacted_at = now();
        }

        if (Schema::hasColumn('leads', 'last_manager_reply_at')) {
            $lead->last_manager_reply_at = now();
        }

        if (Schema::hasColumn('leads', 'status') && in_array((string) $lead->status, ['New', 'Assigned'], true)) {
            $lead->status = 'Attempting Contact';
        }

        $lead->save();
    }

    protected function putIfColumnExists(array &$data, string $table, string $column, mixed $value): void
    {
        if (Schema::hasColumn($table, $column)) {
            $data[$column] = $value;
        }
    }
}