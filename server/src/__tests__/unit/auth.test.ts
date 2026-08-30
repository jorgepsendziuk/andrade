import { describe, expect, it } from 'vitest';
import jwt from 'jsonwebtoken';
import { authMiddleware, signToken, type AuthRequest } from '../../auth.js';
import type { Response } from 'express';

function mockRes() {
  const res = {
    statusCode: 200,
    body: null as unknown,
    status(code: number) {
      this.statusCode = code;
      return this;
    },
    json(payload: unknown) {
      this.body = payload;
      return this;
    },
  };
  return res as Response & { statusCode: number; body: unknown };
}

describe('auth', () => {
  it('signToken gera JWT válido', () => {
    const token = signToken({
      id: '1',
      email: 'admin@test.com',
      name: 'Admin',
      role: 'admin',
    });
    const decoded = jwt.verify(token, process.env.JWT_SECRET!) as { email: string };
    expect(decoded.email).toBe('admin@test.com');
  });

  it('authMiddleware rejeita sem Bearer', () => {
    const req = { headers: {} } as AuthRequest;
    const res = mockRes();
    let nextCalled = false;
    authMiddleware(req, res, () => {
      nextCalled = true;
    });
    expect(nextCalled).toBe(false);
    expect(res.statusCode).toBe(401);
  });

  it('authMiddleware aceita token válido', () => {
    const token = signToken({
      id: '1',
      email: 'admin@test.com',
      name: 'Admin',
      role: 'admin',
    });
    const req = { headers: { authorization: `Bearer ${token}` } } as AuthRequest;
    const res = mockRes();
    let nextCalled = false;
    authMiddleware(req, res, () => {
      nextCalled = true;
    });
    expect(nextCalled).toBe(true);
    expect(req.user?.role).toBe('admin');
  });
});
