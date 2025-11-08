import { useEffect, useState } from 'react';
import { getMe, MePayload } from '../api/auth';

export function useMe() {
  const [data, setData] = useState<MePayload['user'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError]   = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        const res = await getMe();
        if (!mounted) return;
        if (res.ok) setData(res.user ?? null);
        else setError('Failed to load user.');
      } catch (e: any) {
        if (!mounted) return;
        setError(e?.message ?? 'Failed to load user.');
      } finally {
        if (mounted) setLoading(false);
      }
    })();
    return () => { mounted = false; };
  }, []);

  return { user: data, loading, error };
}
