@if(\Illuminate\Support\Facades\Route::has('admin.ajax.jobs-by-client'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientSelect = document.getElementById('client_id');
    const jobSelect = document.getElementById('job_id');
    const selectedJobId = @json(old('job_id', $invoice->job_id));
    const urlTemplate = @json(route('admin.ajax.jobs-by-client', ['client' => 'CLIENT_ID']));

    async function loadJobs(clientId, selectedId = null) {
        jobSelect.innerHTML = '<option value="">Loading jobs...</option>';

        if (!clientId) {
            jobSelect.innerHTML = '<option value="">No linked job</option>';
            return;
        }

        try {
            const response = await fetch(urlTemplate.replace('CLIENT_ID', clientId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const jobs = await response.json();

            jobSelect.innerHTML = '<option value="">No linked job</option>';

            jobs.forEach(function (job) {
                const label = `${job.job_code || ('Job #' + job.id)} - ${String(job.status || '').replace('_', ' ')}`;
                const option = new Option(label, job.id);

                if (String(selectedId) === String(job.id)) {
                    option.selected = true;
                }

                jobSelect.add(option);
            });
        } catch (error) {
            jobSelect.innerHTML = '<option value="">Failed to load jobs</option>';
        }
    }

    clientSelect.addEventListener('change', function () {
        loadJobs(this.value, null);
    });
});
</script>
@endif
