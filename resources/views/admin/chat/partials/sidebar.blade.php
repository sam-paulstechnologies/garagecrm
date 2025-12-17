<div class="overflow-y-auto h-full">

    @forelse ($conversations as $c)
        @php
            $isActive = ($activeId == $c->id);
        @endphp

        <a href="{{ route('admin.chat.show', $c->id) }}"
           class="block">
            <div class="px-4 py-3 border-b transition
                        {{ $isActive ? 'bg-slate-200 font-semibold' : 'bg-white hover:bg-slate-100' }}">

                {{-- Top: Name + Unread --}}
                <div class="flex items-center justify-between">
                    <span class="text-gray-900 truncate max-w-[140px]">
                        {{ $c->customer_name ?? 'Unknown' }}
                    </span>

                    @if (($c->unread_count ?? 0) > 0)
                        <span class="text-xs bg-red-600 text-white px-2 py-0.5 rounded-full">
                            {{ $c->unread_count }}
                        </span>
                    @endif
                </div>

                {{-- Phone --}}
                <div class="text-xs text-gray-500 truncate">
                    {{ $c->customer_phone ?? '-' }}
                </div>

                {{-- Last message preview --}}
                @if ($c->last_message_preview)
                    <div class="text-xs text-gray-600 mt-1 line-clamp-2">
                        {{ $c->last_message_preview }}
                    </div>
                @endif

                {{-- Time --}}
                <div class="text-[10px] text-gray-400 mt-1">
                    {{ optional($c->last_message_at)->diffForHumans() }}
                </div>
            </div>
        </a>

    @empty
        <div class="p-4 text-gray-400 text-center">
            No conversations yet.
        </div>
    @endforelse

</div>
