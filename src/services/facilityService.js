const API_BASE = 'http://localhost:8000'

const request = async (path, options = {}) => {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {})
    },
    ...options
  })

  if (!res.ok) {
    throw new Error(options.errorMessage || 'Facility request failed.')
  }

  return res.status === 204 ? null : res.json()
}

export const getFacilities = () => request('/facilities', {
  errorMessage: 'Could not retrieve facilities.'
})

export const createFacility = (facility) => request('/facilities', {
  method: 'POST',
  body: JSON.stringify(facility),
  errorMessage: 'Could not create facility.'
})

export const updateFacility = (id, updates) => request(`/facilities/${id}`, {
  method: 'PATCH',
  body: JSON.stringify(updates),
  errorMessage: 'Could not update facility.'
})

export const deleteFacility = (id) => request(`/facilities/${id}`, {
  method: 'DELETE',
  errorMessage: 'Could not delete facility.'
})
