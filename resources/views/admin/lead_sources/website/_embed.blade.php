<!-- SayaraForce Website Lead Form -->
<div id="sayaraforce-lead-form-wrapper">
    <style>
        #sayaraforce-lead-form-wrapper {
            font-family: Arial, sans-serif;
            max-width: 460px;
            width: 100%;
        }

        #sayaraforce-lead-form-wrapper .sf-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        #sayaraforce-lead-form-wrapper .sf-header {
            margin-bottom: 18px;
        }

        #sayaraforce-lead-form-wrapper .sf-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        #sayaraforce-lead-form-wrapper .sf-title {
            margin: 0;
            font-size: 22px;
            line-height: 1.25;
            font-weight: 800;
            color: #0f172a;
        }

        #sayaraforce-lead-form-wrapper .sf-subtitle {
            margin: 6px 0 0;
            font-size: 14px;
            line-height: 1.5;
            color: #64748b;
        }

        #sayaraforce-lead-form-wrapper .sf-field {
            margin-bottom: 12px;
        }

        #sayaraforce-lead-form-wrapper .sf-label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }

        #sayaraforce-lead-form-wrapper .sf-input,
        #sayaraforce-lead-form-wrapper .sf-textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        #sayaraforce-lead-form-wrapper .sf-input:focus,
        #sayaraforce-lead-form-wrapper .sf-textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        #sayaraforce-lead-form-wrapper .sf-textarea {
            resize: vertical;
            min-height: 96px;
        }

        #sayaraforce-lead-form-wrapper .sf-button {
            width: 100%;
            border: none;
            border-radius: 14px;
            padding: 13px 16px;
            background: linear-gradient(135deg, #2563eb, #f97316);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        #sayaraforce-lead-form-wrapper .sf-button:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        #sayaraforce-lead-form-wrapper .sf-button:disabled {
            cursor: not-allowed;
            opacity: 0.65;
            transform: none;
        }

        #sayaraforce-lead-form-wrapper .sf-message {
            margin-top: 12px;
            min-height: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        #sayaraforce-lead-form-wrapper .sf-message.sf-info {
            color: #2563eb;
        }

        #sayaraforce-lead-form-wrapper .sf-message.sf-success {
            color: #15803d;
        }

        #sayaraforce-lead-form-wrapper .sf-message.sf-error {
            color: #dc2626;
        }

        #sayaraforce-lead-form-wrapper .sf-footer {
            margin-top: 14px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>

    <div class="sf-card">
        <div class="sf-header">
            <div class="sf-badge">
                🚗 Service Enquiry
            </div>

            <h3 class="sf-title">
                Request a Callback
            </h3>

            <p class="sf-subtitle">
                Share your details and our team will contact you shortly.
            </p>
        </div>

        <form method="POST"
              action="{{ route('api.website-leads.store', $leadSource->form_token) }}"
              onsubmit="return sayaraforceSubmitLead(event)">

            <div class="sf-field">
                <label class="sf-label">Your Name *</label>
                <input type="text"
                       name="name"
                       placeholder="Enter your full name"
                       required
                       class="sf-input">
            </div>

            <div class="sf-field">
                <label class="sf-label">Phone Number *</label>
                <input type="tel"
                       name="phone"
                       placeholder="Enter your phone number"
                       required
                       class="sf-input">
            </div>

            <div class="sf-field">
                <label class="sf-label">Email</label>
                <input type="email"
                       name="email"
                       placeholder="Enter your email address"
                       class="sf-input">
            </div>

            <div class="sf-field">
                <label class="sf-label">Message</label>
                <textarea name="message"
                          placeholder="Tell us what service you need"
                          rows="4"
                          class="sf-textarea"></textarea>
            </div>

            <button type="submit" class="sf-button">
                Submit Enquiry
            </button>

            <div class="sf-message" id="sayaraforce-lead-form-msg"></div>
        </form>

        <div class="sf-footer">
            Powered by SayaraForce
        </div>
    </div>
</div>

<script>
function sayaraforceSubmitLead(e) {
    e.preventDefault();

    const form = e.target;
    const msg = form.querySelector('#sayaraforce-lead-form-msg');
    const button = form.querySelector('button[type="submit"]');

    msg.className = 'sf-message sf-info';
    msg.innerText = 'Submitting your enquiry...';

    button.disabled = true;
    button.innerText = 'Submitting...';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
    .then(function (res) {
        return res.json().then(function (data) {
            return {
                ok: res.ok,
                data: data
            };
        });
    })
    .then(function (response) {
        if (response.ok) {
            msg.className = 'sf-message sf-success';
            msg.innerText = '✅ Thank you! We will contact you shortly.';
            form.reset();
        } else {
            msg.className = 'sf-message sf-error';
            msg.innerText = '❌ ' + (response.data.message || 'Submission failed. Please try again.');
        }
    })
    .catch(function () {
        msg.className = 'sf-message sf-error';
        msg.innerText = '❌ Network error. Please try again.';
    })
    .finally(function () {
        button.disabled = false;
        button.innerText = 'Submit Enquiry';
    });

    return false;
}
</script>
<!-- End SayaraForce Website Lead Form -->