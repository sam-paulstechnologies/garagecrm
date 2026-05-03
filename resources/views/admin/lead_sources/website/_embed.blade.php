<!-- GarageCRM Website Lead Form -->
<div id="garagecrm-lead-form">
    <form method="POST"
          action="{{ route('api.website-leads.store', $leadSource->form_token) }}"
          onsubmit="return garagecrmSubmitLead(event)"
          style="font-family: Arial, sans-serif; max-width: 420px;">

        <input type="text"
               name="name"
               placeholder="Your Name"
               required
               style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;" />

        <input type="tel"
               name="phone"
               placeholder="Phone Number"
               required
               style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;" />

        <input type="email"
               name="email"
               placeholder="Email (optional)"
               style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;" />

        <textarea name="message"
                  placeholder="Message (optional)"
                  rows="4"
                  style="width:100%;padding:10px;margin-bottom:12px;border:1px solid #ccc;border-radius:6px;"></textarea>

        <button type="submit"
                style="width:100%;padding:12px;background:#111827;color:#fff;
                       border:none;border-radius:8px;font-size:15px;font-weight:600;
                       cursor:pointer;">
            Submit
        </button>

        <div id="garagecrm-msg"
             style="margin-top:10px;font-size:14px;"></div>
    </form>
</div>

<script>
function garagecrmSubmitLead(e) {
    e.preventDefault();

    const form = e.target;
    const msg = form.querySelector('#garagecrm-msg');
    msg.innerText = 'Submitting…';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
    .then(res => res.json().then(data => ({ ok: res.ok, data })))
    .then(({ ok, data }) => {
        if (ok) {
            msg.innerText = '✅ Thank you! We will contact you shortly.';
            form.reset();
        } else {
            msg.innerText = '❌ ' + (data.message || 'Submission failed');
        }
    })
    .catch(() => {
        msg.innerText = '❌ Network error. Please try again.';
    });

    return false;
}
</script>
<!-- End GarageCRM Website Lead Form -->
