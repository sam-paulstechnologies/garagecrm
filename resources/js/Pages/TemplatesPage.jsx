import React, { useEffect, useState } from 'react';
import axios from 'axios';

const TemplatesPage = () => {
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios.get('/admin/templates/data')
      .then(res => {
        setTemplates(res.data);
        setLoading(false);
      })
      .catch(err => {
        console.error('❌ Error fetching templates:', err);
        setLoading(false);
      });
  }, []);

  if (loading) return <p>Loading...</p>;

  return (
    <div className="p-4">
      <h1 className="text-2xl font-bold mb-4">📄 Templates Page</h1>
      {templates.length === 0 ? (
        <p>No templates found.</p>
      ) : (
        <ul className="list-disc list-inside">
          {templates.map(template => (
            <li key={template.id}>🚀 {template.name}</li>
          ))}
        </ul>
      )}
    </div>
  );
};

export default TemplatesPage;
