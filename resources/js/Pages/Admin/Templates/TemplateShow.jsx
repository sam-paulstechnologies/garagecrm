import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';

const TemplateShow = () => {
  const { id } = useParams();
  const [template, setTemplate] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`/admin/templates/${id}`)
      .then(res => res.json())
      .then(data => {
        setTemplate(data);
        setLoading(false);
      })
      .catch(err => {
        console.error("Failed to fetch template", err);
        setLoading(false);
      });
  }, [id]);

  if (loading) return <div className="p-4">Loading...</div>;
  if (!template) return <div className="p-4 text-red-600">Template not found.</div>;

  return (
    <div className="p-6 max-w-2xl mx-auto bg-white shadow-md rounded-lg">
      <h1 className="text-2xl font-bold mb-4">View Template</h1>

      <div className="space-y-4">
        <div>
          <span className="font-semibold">Name:</span> {template.name}
        </div>
        <div>
          <span className="font-semibold">Category:</span> {template.category || '-'}
        </div>
        <div>
          <span className="font-semibold">Type:</span> {template.type}
        </div>
        <div>
          <span className="font-semibold">Global:</span> {template.is_global ? '✔️' : '❌'}
        </div>
        <div>
          <span className="font-semibold">Content:</span>
          <pre className="whitespace-pre-wrap bg-gray-100 p-3 mt-1 rounded text-sm">{template.content}</pre>
        </div>
      </div>

      <Link to="/admin/templates" className="mt-6 inline-block text-blue-600 hover:underline">
        ← Back to list
      </Link>
    </div>
  );
};

export default TemplateShow;
