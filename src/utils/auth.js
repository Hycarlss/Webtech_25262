/**
 * Generates a mock JWT token string in the format header.payload.signature
 * @param {Object} user 
 * @returns {string} jwt
 */
export const generateMockJWT = (user) => {
  const header = btoa(JSON.stringify({ alg: "HS256", typ: "JWT" }));
  const payload = btoa(JSON.stringify({
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role || 'student',
    // Student specific fields if available
    matrixNumber: user.matrixNumber || '',
    hostelBlock: user.hostelBlock || '',
    roomNumber: user.roomNumber || '',
    phone: user.phone || '',
    exp: Date.now() + 2 * 60 * 60 * 1000 // expires in 2 hours
  }));
  const signature = "mock_signature_key";
  return `${header}.${payload}.${signature}`;
};

/**
 * Decodes a mock JWT payload
 * @param {string} token 
 * @returns {Object|null} payload
 */
export const decodeMockJWT = (token) => {
  if (!token) return null;
  try {
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    
    // btoa/atob helper handles standard base64 decoding
    const payloadStr = atob(parts[1]);
    return JSON.parse(payloadStr);
  } catch (e) {
    console.error("Failed to decode mock JWT token", e);
    return null;
  }
};

/**
 * Check if the mock token is expired
 * @param {string} token 
 * @returns {boolean}
 */
export const isTokenExpired = (token) => {
  const decoded = decodeMockJWT(token);
  if (!decoded) return true;
  return decoded.exp ? Date.now() > decoded.exp : true;
};
