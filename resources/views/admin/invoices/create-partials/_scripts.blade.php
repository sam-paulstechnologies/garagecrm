@if(\Illuminate\Support\Facades\Route::has('admin.ajax.jobs-by-client'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientSelect = document.getElementById('client_id');
    const jobSelect = document.getElementById('job_id');
    const selectedJobId = @json(old('job_id'));
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

            if (!Array.isArray(jobs) || jobs.length === 0) {
                const option = new Option('No jobs found for this client', '');
                jobSelect.add(option);
                return;
            }

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

    if (clientSelect.value) {
        loadJobs(clientSelect.value, selectedJobId);
    }
});
</script>
@else
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientSelect = document.getElementById('client_id');
    const jobSelect = document.getElementById('job_id');
    const allOptions = Array.from(jobSelect.querySelectorAll('option[data-client-id]'));

    function filterJobs(clientId) {
        const selected = jobSelect.value;

        jobSelect.innerHTML = '<option value="">No linked job</option>';

        allOptions.forEach(function (option) {
            if (!clientId || String(option.dataset.clientId) === String(clientId)) {
                jobSelect.add(option.cloneNode(true));
            }
        });

        if (selected) {
            jobSelect.value = selected;
        }
    }

    clientSelect.addEventListener('change', function () {
        filterJobs(this.value);
    });

    filterJobs(clientSelect.value);
});
</script>
@endif
