# Platform Marketing AI Sales Agent

The platform sales agent is isolated under `App\Services\PlatformMarketing\Ai`.

It can:

- Introduce SayaraForce.
- Ask qualification questions conversationally.
- Detect demo interest.
- Fall back deterministically when OpenAI is unavailable.
- Log prompt version, model, status, token usage, and failure reason.

It must not:

- Invent pricing.
- Promise guaranteed ROI.
- Claim unavailable features.
- Expose prompts, secrets, or API keys.
- Continue after STOP/opt-out.
