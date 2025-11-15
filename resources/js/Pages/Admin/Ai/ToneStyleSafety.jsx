import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';

export default function ToneStyleSafety({ config }) {
  const { data, setData, post, processing, recentlySuccessful } = useForm(config);
  const [stopWord, setStopWord] = useState('');
  const [topic, setTopic] = useState('');

  const addStop = () => {
    if (!stopWord.trim()) return;
    setData('stop_words', [...(data.stop_words || []), stopWord.trim()]);
    setStopWord('');
  };
  const removeStop = (i) => {
    const next = [...data.stop_words];
    next.splice(i, 1);
    setData('stop_words', next);
  };

  const addTopic = () => {
    if (!topic.trim()) return;
    setData('forbidden_topics', [...(data.forbidden_topics || []), topic.trim()]);
    setTopic('');
  };
  const removeTopic = (i) => {
    const next = [...data.forbidden_topics];
    next.splice(i, 1);
    setData('forbidden_topics', next);
  };

  const onSubmit = (e) => {
    e.preventDefault();
    post(route('admin.ai.tone.save'));
  };

  const exampleInput = 'hey can you pick up my car and also what’s the price?';
  const preview = (() => {
    let txt = exampleInput;
    if (data.tone_preset === 'professional') txt = txt.replace(/^hey/i, 'Hello');
    if (data.tone_preset === 'friendly') txt = txt.replace(/^hey/i, 'Hi');
    const max = Number(data.reply_length_limit || 480);
    if (data.truncate_over_limit && txt.length > max)
      txt = txt.slice(0, max - 1) + '…';
    return txt;
  })();

  return (
    <AppLayout>
      <Head title="AI — Tone, Style & Safety" />
      <div className="max-w-4xl mx-auto space-y-8">
        <h1 className="text-2xl font-semibold">Tone, Style & Safety</h1>

        <form onSubmit={onSubmit} className="space-y-6 bg-white p-6 rounded-xl shadow">
          {/* Tone */}
          <div>
            <label className="font-medium">Tone preset</label>
            <div className="mt-2 flex gap-4">
              {['friendly', 'professional', 'custom'].map((opt) => (
                <label key={opt} className="flex items-center gap-2">
                  <input
                    type="radio"
                    name="tone"
                    checked={data.tone_preset === opt}
                    onChange={() => setData('tone_preset', opt)}
                  />
                  <span className="capitalize">{opt}</span>
                </label>
              ))}
            </div>
            {data.tone_preset === 'custom' && (
              <textarea
                className="mt-3 w-full border rounded p-2"
                rows={4}
                placeholder="Write custom style guidelines…"
                value={data.custom_style_guidelines || ''}
                onChange={(e) => setData('custom_style_guidelines', e.target.value)}
              />
            )}
          </div>

          {/* Length & rate */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="font-medium">Reply length limit (chars)</label>
              <input
                type="number"
                className="mt-2 w-full border rounded p-2"
                min={100}
                max={2000}
                value={data.reply_length_limit}
                onChange={(e) =>
                  setData('reply_length_limit', Number(e.target.value))
                }
              />
              <label className="mt-3 flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={!!data.truncate_over_limit}
                  onChange={(e) =>
                    setData('truncate_over_limit', e.target.checked)
                  }
                />
                Truncate if over limit
              </label>
            </div>

            <div>
              <label className="font-medium">Rate limits</label>
              <div className="mt-2 grid grid-cols-2 gap-3">
                <div>
                  <div className="text-sm text-gray-600">Per minute</div>
                  <input
                    type="number"
                    className="w-full border rounded p-2"
                    min={1}
                    max={120}
                    value={data.rate_limit?.per_minute}
                    onChange={(e) =>
                      setData('rate_limit', {
                        ...data.rate_limit,
                        per_minute: Number(e.target.value),
                      })
                    }
                  />
                </div>
                <div>
                  <div className="text-sm text-gray-600">Burst</div>
                  <input
                    type="number"
                    className="w-full border rounded p-2"
                    min={1}
                    max={240}
                    value={data.rate_limit?.burst}
                    onChange={(e) =>
                      setData('rate_limit', {
                        ...data.rate_limit,
                        burst: Number(e.target.value),
                      })
                    }
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Safety */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="font-medium">Stop words</label>
              <div className="mt-2 flex gap-2">
                <input
                  className="flex-1 border rounded p-2"
                  placeholder="Add a stop word e.g., STOP"
                  value={stopWord}
                  onChange={(e) => setStopWord(e.target.value)}
                />
                <button
                  type="button"
                  className="px-3 py-2 bg-gray-900 text-white rounded"
                  onClick={addStop}
                >
                  Add
                </button>
              </div>
              <div className="mt-2 flex flex-wrap gap-2">
                {(data.stop_words || []).map((w, i) => (
                  <span key={i} className="px-2 py-1 bg-gray-100 rounded text-sm">
                    {w}{' '}
                    <button
                      type="button"
                      className="ml-1 text-gray-500"
                      onClick={() => removeStop(i)}
                    >
                      ×
                    </button>
                  </span>
                ))}
              </div>

              <label className="mt-4 flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={!!data.handoff_on_stop_word}
                  onChange={(e) =>
                    setData('handoff_on_stop_word', e.target.checked)
                  }
                />
                Auto-handoff when a stop word appears
              </label>
            </div>

            <div>
              <label className="font-medium">Forbidden topics</label>
              <div className="mt-2 flex gap-2">
                <input
                  className="flex-1 border rounded p-2"
                  placeholder="Add a forbidden topic"
                  value={topic}
                  onChange={(e) => setTopic(e.target.value)}
                />
                <button
                  type="button"
                  className="px-3 py-2 bg-gray-900 text-white rounded"
                  onClick={addTopic}
                >
                  Add
                </button>
              </div>
              <div className="mt-2 flex flex-wrap gap-2">
                {(data.forbidden_topics || []).map((w, i) => (
                  <span key={i} className="px-2 py-1 bg-gray-100 rounded text-sm">
                    {w}{' '}
                    <button
                      type="button"
                      className="ml-1 text-gray-500"
                      onClick={() => removeTopic(i)}
                    >
                      ×
                    </button>
                  </span>
                ))}
              </div>

              <div className="mt-4">
                <label className="font-medium">Safety mode</label>
                <select
                  className="mt-2 w-full border rounded p-2"
                  value={data.safe_mode}
                  onChange={(e) => setData('safe_mode', e.target.value)}
                >
                  <option value="off">Off</option>
                  <option value="soft">Soft</option>
                  <option value="strict">Strict</option>
                </select>
              </div>
            </div>
          </div>

          {/* Preview */}
          <div className="border rounded p-4 bg-gray-50">
            <div className="text-sm text-gray-600 mb-2">Preview (local)</div>
            <div className="p-3 bg-white rounded border">{preview}</div>
          </div>

          <div className="flex items-center gap-3">
            <button
              type="submit"
              disabled={processing}
              className="px-4 py-2 bg-gray-900 text-white rounded"
            >
              Save
            </button>
            {recentlySuccessful && (
              <span className="text-green-600 text-sm">Saved ✓</span>
            )}
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
