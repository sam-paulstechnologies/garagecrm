import React, { useEffect, useState } from 'react';
import axios from 'axios';

function App() {
  const [message, setMessage] = useState('Loading...');

  useEffect(() => {
    axios.get('http://127.0.0.1:8000/test-connection')
      .then((res) => setMessage(res.data.message))
      .catch((err) => {
        console.error('FULL AXIOS ERROR:', err.response || err.message || err);
        setMessage('API call failed');
      });
  }, []);

  return (
    <div style={{ padding: '2rem', fontFamily: 'sans-serif' }}>
      <h1>Garage CRM - Admin Panel</h1>
      <p>{message}</p>
    </div>
  );
}

export default App;
