import React, { useState } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';

const TemplateBuilder = () => {
  const [form, setForm] = useState({
    name: '',
    category: '',
    type: 'email',
    content: ''
  });

  const [errors, setErrors] = useState({});
  const navigate = useNavigate();

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});

    try {
      await axios.post('/admin/templates', form);
      navigate('/admin/templates');
    } catch (error) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors || {});
      } else {
        alert('Something went wrong!');
      }
    }
  };

  return (
    <div className="max-w-4xl mx-auto bg-white rounded shadow p-6 mt-6">
      <h2 className="text-xl font-bold mb-4">📋 Create Template</h2>

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Name */}
        <div>
          <label className="block text-sm font-medium mb-1">Name</label>
          <input
            type="text"
            name="name"
            className="w-full border px-3 py-2 rounded"
            value={form.name}
            onChange={handleChange}
            required
          />
          {errors.name && <p className="text-red-500 text-sm">{errors.name[0]}</p>}
        </div>

        {/* Category */}
        <div>
          <label className="block text-sm font-medium mb-1">Category</label>
          <input
            type="text"
            name="category"
            className="w-full border px-3 py-2 rounded"
            value={form.category}
            onChange={handleChange}
            required
          />
          {errors.category && <p className="text-red-500 text-sm">{errors.category[0]}</p>}
        </div>

        {/* Type */}
        <div>
          <label className="block text-sm font-medium mb-1">Type</label>
          <select
            name="type"
            className="w-full border px-3 py-2 rounded"
            value={form.type}
            onChange={handleChange}
            required
          >
            <option value="email">Email</option>
            <option value="sms">SMS</option>
            <option value="whatsapp">WhatsApp</option>
          </select>
          {errors.type && <p className="text-red-500 text-sm">{errors.type[0]}</p>}
        </div>

        {/* Content */}
        <div>
          <label className="block text-sm font-medium mb-1">Content</label>
          <textarea
            name="content"
            className="w-full border px-3 py-2 rounded"
            rows={6}
            value={form.content}
            onChange={handleChange}
            required
          />
          {errors.content && <p className="text-red-500 text-sm">{errors.content[0]}</p>}
        </div>

        <button
          type="submit"
          className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition"
        >
          Save Template
        </button>
      </form>
    </div>
  );
};

export default TemplateBuilder;
