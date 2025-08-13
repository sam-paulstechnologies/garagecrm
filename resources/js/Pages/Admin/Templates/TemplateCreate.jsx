import React, { useState } from 'react';
import axios from 'axios';
import whatsappBg from '../../../../images/whatsapp-preview-bg.png';
import smsBg from '../../../../images/sms-preview-bg.png';
import emailBg from '../../../../images/email-preview-bg.png';

const TemplateCreate = () => {
  const [formData, setFormData] = useState({
    name: '',
    category: '',
    type: 'whatsapp',
    content: '',
  });

  const handleChange = (e) => {
    setFormData((prev) => ({
      ...prev,
      [e.target.name]: e.target.value,
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    axios
      .post('/admin/templates', formData)
      .then(() => {
        alert('Template created!');
        window.location.href = '/admin/templates';
      })
      .catch((err) => {
        console.error(err);
        alert('Error creating template.');
      });
  };

  const getPreviewBackground = () => {
    switch (formData.type) {
      case 'sms':
        return smsBg;
      case 'email':
        return emailBg;
      default:
        return whatsappBg;
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 p-6">
      <div className="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
        {/* Left: Form */}
        <div className="bg-white p-6 rounded shadow">
          <h2 className="text-2xl font-semibold mb-6">Create Template</h2>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium">Name</label>
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleChange}
                required
                className="w-full border border-gray-300 rounded px-3 py-2"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">Category</label>
              <input
                type="text"
                name="category"
                value={formData.category}
                onChange={handleChange}
                required
                className="w-full border border-gray-300 rounded px-3 py-2"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">Type</label>
              <select
                name="type"
                value={formData.type}
                onChange={handleChange}
                required
                className="w-full border border-gray-300 rounded px-3 py-2"
              >
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="whatsapp">WhatsApp</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium">Content</label>
              <textarea
                name="content"
                value={formData.content}
                onChange={handleChange}
                required
                rows={6}
                className="w-full border border-gray-300 rounded px-3 py-2"
              />
            </div>

            <button
              type="submit"
              className="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition"
            >
              Save Template
            </button>
          </form>
        </div>

        {/* Right: Preview */}
        <div className="flex justify-center">
          <div
            className="w-[360px] h-[640px] border rounded-[2rem] shadow-md overflow-hidden relative bg-cover bg-no-repeat bg-center"
            style={{ backgroundImage: `url(${getPreviewBackground()})` }}
          >
            <div className="bg-green-600 text-white px-4 py-2 text-sm font-bold uppercase">
              {formData.type}
            </div>

            <div className="flex flex-col justify-end h-full pb-6 px-4">
              <div className="flex justify-end">
                <div className="bg-green-100 text-sm rounded-lg px-4 py-2 max-w-[80%] shadow">
                  {formData.content || 'Your message will appear here...'}
                  <div className="text-right text-xs text-gray-500 mt-1">
                    12:45 PM
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TemplateCreate;
