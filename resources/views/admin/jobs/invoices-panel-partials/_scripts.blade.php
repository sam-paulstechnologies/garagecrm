<script>
(function(){
    const openers = [
        document.getElementById('{{ $openBtnId }}'),
        document.getElementById('{{ $openBtnId }}-empty'),
        document.getElementById('{{ $openBtnId }}-below')
    ].filter(Boolean);

    const modal = document.getElementById('{{ $modalId }}');

    const closers = [
        document.getElementById('{{ $closeBtnId }}'),
        document.getElementById('{{ $closeBtnId }}-2')
    ].filter(Boolean);

    openers.forEach(btn => btn.addEventListener('click', () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }));

    closers.forEach(btn => btn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }));

    modal?.addEventListener('click', (e) => {
        if(e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
})();
</script>
