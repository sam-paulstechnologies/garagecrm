<?php

namespace App\Services;

use App\Mail\SimpleNotification;
use App\Models\Shared\CommunicationLog;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Centralized comms service: WhatsApp + Email
 * - Single responsibility for logging into communication_logs
 * - Consistent templates + error handling
 */
class Notifier
{
    public function __construct(
        protected TwilioWhatsApp $wa // ensure you have App\Services\TwilioWhatsApp bound
    ) {}

    /* =========================
     * LEAD MESSAGES
     * ========================= */

    public function sendLeadCreatedWhatsApp($lead, string $toPhone): void
    {
        $body = sprintf(
            'Hi %s, thanks for contacting us. Your lead no: %s.',
            $lead->name ?: 'there',
            $lead->id
        );

        $this->sendWhatsAppAndLog(
            toPhone: $toPhone,
            body: $body,
            template: 'lead_created',
            clientId: $lead->client_id ?? null,
            leadId: $lead->id ?? null,
            companyId: $lead->company_id ?? null
        );
    }

    public function sendLeadCreatedEmail($lead, string $toEmail): void
    {
        $subject = 'Thanks for contacting us';
        $body    = "Hi {$lead->name}, thanks for contacting us. Your lead no: {$lead->id}.";

        $this->sendEmailAndLog(
            toEmail: $toEmail,
            subject: $subject,
            body: $body,
            template: 'lead_created',
            clientId: $lead->client_id ?? null,
            leadId: $lead->id ?? null,
            companyId: $lead->company_id ?? null
        );
    }

    /* =========================
     * OPPORTUNITY MESSAGES
     * ========================= */

    /**
     * $templateKey: opportunity_confirmed | opportunity_cancelled | opportunity_rescheduled | job_completed
     * $vars: array plugged into vsprintf for the selected template
     */
    public function sendOppStatusWhatsApp($opp, string $toPhone, string $templateKey, array $vars = []): void
    {
        $map = [
            'opportunity_confirmed'   => 'Hi %s, your booking/opportunity %s is confirmed.',
            'opportunity_cancelled'   => 'Hi %s, your booking %s has been cancelled as requested.',
            'opportunity_rescheduled' => 'Hi %s, we rescheduled your booking to %s.',
            'job_completed'           => 'Hi %s, your job %s is complete. Invoice: %s',
        ];

        $tpl  = $map[$templateKey] ?? '%s';
        $body = vsprintf($tpl, $vars);

        $this->sendWhatsAppAndLog(
            toPhone: $toPhone,
            body: $body,
            template: $templateKey,
            clientId: $opp->client_id ?? null,
            opportunityId: $opp->id ?? null,
            companyId: $opp->company_id ?? null
        );
    }

    public function sendOppStatusEmail($opp, string $toEmail, string $templateKey, array $vars = []): void
    {
        $map = [
            'opportunity_confirmed'   => ['Subj' => 'Booking Confirmed',   'Tpl' => 'Hi %s, your booking/opportunity %s is confirmed.'],
            'opportunity_cancelled'   => ['Subj' => 'Booking Cancelled',   'Tpl' => 'Hi %s, your booking %s has been cancelled as requested.'],
            'opportunity_rescheduled' => ['Subj' => 'Booking Rescheduled', 'Tpl' => 'Hi %s, we rescheduled your booking to %s.'],
            'job_completed'           => ['Subj' => 'Job Completed',       'Tpl' => 'Hi %s, your job %s is complete. Invoice: %s'],
        ];

        $def  = $map[$templateKey] ?? ['Subj' => 'Update', 'Tpl' => '%s'];
        $body = vsprintf($def['Tpl'], $vars);

        $this->sendEmailAndLog(
            toEmail: $toEmail,
            subject: $def['Subj'],
            body: $body,
            template: $templateKey,
            clientId: $opp->client_id ?? null,
            opportunityId: $opp->id ?? null,
            companyId: $opp->company_id ?? null
        );
    }

    /* =========================
     * LOW-LEVEL SEND + LOG
     * ========================= */

    protected function sendWhatsAppAndLog(
        string $toPhone,
        string $body,
        string $template,
        ?int $clientId = null,
        ?int $leadId = null,
        ?int $opportunityId = null,
        ?int $companyId = null
    ): void {
        $res = null;
        $ok  = false;
        $err = null;
        $sid = null;

        try {
            $res = $this->wa->send($toPhone, $body); // expects ['ok'=>bool, 'sid'=>?, 'error'=>?]
            $ok  = (bool)($res['ok'] ?? false);
            $sid = $res['sid'] ?? null;
            $err = $res['error'] ?? null;
        } catch (Throwable $e) {
            $err = $e->getMessage();
        }

        $this->log([
            'company_id'        => $companyId,
            'client_id'         => $clientId,
            'lead_id'           => $leadId,
            'opportunity_id'    => $opportunityId,
            'channel'           => 'whatsapp',
            'direction'         => 'outbound',
            'template'          => $template,
            'to_phone'          => $toPhone,
            'body'              => $body,
            'provider_sid'      => $sid,
            'communication_date'=> now(),
            'follow_up_required'=> 0,
            'meta'              => [
                'ok'    => $ok,
                'error' => $err,
                'raw'   => $res,
            ],
        ]);
    }

    protected function sendEmailAndLog(
        string $toEmail,
        string $subject,
        string $body,
        string $template,
        ?int $clientId = null,
        ?int $leadId = null,
        ?int $opportunityId = null,
        ?int $companyId = null
    ): void {
        $err = null;

        try {
            Mail::to($toEmail)->send(new SimpleNotification($subject, $body));
        } catch (Throwable $e) {
            $err = $e->getMessage();
        }

        // Note: Most mailers don’t return a message-id here; capture if you wire a custom transport.
        $this->log([
            'company_id'        => $companyId,
            'client_id'         => $clientId,
            'lead_id'           => $leadId,
            'opportunity_id'    => $opportunityId,
            'channel'           => 'email',
            'direction'         => 'outbound',
            'template'          => $template,
            'to_email'          => $toEmail,
            'body'              => $body,
            'provider_sid'      => null,
            'communication_date'=> now(),
            'follow_up_required'=> 0,
            'meta'              => [
                'error' => $err,
            ],
        ]);
    }

    protected function log(array $attrs): void
    {
        // Accepts snake_case keys matching your table columns.
        CommunicationLog::create($attrs);
    }
}
