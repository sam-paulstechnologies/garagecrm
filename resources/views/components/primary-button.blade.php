<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-10 items-center rounded-xl border border-transparent bg-[#FF6A00] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-[#E85F00] focus:outline-none focus:ring-2 focus:ring-[#FF6A00] focus:ring-offset-2 active:bg-[#C95200]']) }}>
    {{ $slot }}
</button>
