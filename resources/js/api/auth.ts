import http from '../lib/http';

export type MePayload = {
  ok: boolean;
  user: {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    role: string | null;
    status: number;
    must_change_password: boolean;
    company?: { id: number; name: string } | null;
    garage?:  { id: number; name: string } | null;
  } | null;
};

export async function getMe(): Promise<MePayload> {
  const { data } = await http.get<MePayload>('/me');
  return data;
}
