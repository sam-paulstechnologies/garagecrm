import React, { useEffect, useState } from 'react';
import axios from 'axios';

const TemplateList = () => {
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get('/admin/templates') // ✅ Corrected route path (no /api)
      .then((response) => {
        const data = Array.isArray(response.data)
          ? response.data
          : Array.isArray(response.data.data)
          ? response.data.data
          : [];

        setTemplates(data);
      })
      .catch((error) => {
        console.error('Error fetching templates:', error);
        setTemplates([]);
      })
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="min-h-screen bg-gray-100 p-6">
      <div className="max-w-6xl mx-auto bg-white rounded-xl shadow-md p-6">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-semibold text-gray-800">📦 Templates</h1>
          <button className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            + Create Template
          </button>
        </div>

        <div className="overflow-x-auto">
          {loading ? (
            <p className="text-center text-gray-500 italic">Loading templates...</p>
          ) : (
            <table className="min-w-full table-auto border border-gray-200 text-sm">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-2 border">Name</th>
                  <th className="px-4 py-2 border">Category</th>
                  <th className="px-4 py-2 border">Type</th>
                  <th className="px-4 py-2 border text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                {templates.length > 0 ? (
                  templates.map((template) => (
                    <tr key={template.id} className="hover:bg-gray-50">
                      <td className="px-4 py-2 border font-medium">{template.name}</td>
                      <td className="px-4 py-2 border">{template.category || '-'}</td>
                      <td className="px-4 py-2 border capitalize">{template.type}</td>
                      <td className="px-4 py-2 border text-center">
                        <button className="text-indigo-600 hover:underline mr-3">View</button>
                        <button className="text-red-600 hover:underline">Delete</button>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="4" className="px-4 py-6 text-center text-gray-500 italic">
                      No templates found.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
};

export default TemplateList;
