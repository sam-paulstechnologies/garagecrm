<script>
    function copyWebhookUrl() {
        const el = document.getElementById('webhookUrlInput');

        if (!el) {
            return;
        }

        el.select();
        el.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(el.value).then(function () {
            alert('Webhook URL copied');
        }).catch(function () {
            document.execCommand('copy');
            alert('Webhook URL copied');
        });
    }
</script>
