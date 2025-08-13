import React, { useEffect, useState } from 'react';
import axios from 'axios';
import AdminHeader from '../../../components/AdminHeader'; // ✅ Adjusted path

const Templates = () => {
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get('/api/admin/templates')
      .then(res => setTemplates(res.data))
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <div className="text-center mt-10 text-gray-600">Loading templates...</div>;
  }

  return (
    <div className="min-h-screen bg-gray-100">
      {/* ✅ This should now render correctly */}
      <AdminHeader />

      <div className="max-w-6xl mx-auto p-6">
        <div className="bg-white shadow rounded-xl p-6 mb-6">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                📄 Templates
              </h1>
              <p className="text-sm text-gray-500 mt-1">
                Manage your templates for campaigns and communications.
              </p>
            </div>
            <button
              className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm"
              onClick={() => alert("Create Template clicked")}
            >
              + Create Template
            </button>
          </div>
        </div>

        {/* Template Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {templates.map(template => (
            <div
              key={template.id}
              className="bg-white shadow rounded-xl p-5 border border-gray-200 hover:shadow-md transition"
            >
              <div className="flex justify-between items-center mb-3">
                <h2 className="text-xl font-semibold text-gray-800">{template.name}</h2>
                <span
                  className={`text-sm px-2 py-1 rounded-full ${
                    template.type === 'email'
                      ? 'bg-blue-100 text-blue-700'
                      : template.type === 'whatsapp'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-gray-200 text-gray-700'
                  }`}
                >
                  {template.type.toUpperCase()}
                </span>
              </div>
              <p className="text-gray-700 text-sm mb-4">{template.content}</p>
              <div className="flex gap-2">
                <button className="text-sm px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">View</button>
                <button className="text-sm px-3 py-1 rounded bg-yellow-200 hover:bg-yellow-300">Edit</button>
                <button className="text-sm px-3 py-1 rounded bg-red-200 hover:bg-red-300">Delete</button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Templates;
