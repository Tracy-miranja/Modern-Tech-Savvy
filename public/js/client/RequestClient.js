import { BASE_URL } from './config.js';

class RequestClient {
    constructor() {
        this.baseURL = BASE_URL;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    getHeaders(hasFile = false) {
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
        };

        if (!hasFile) {
            headers['Content-Type'] = 'application/json';
        }

        return headers;
    }

    async request(method, endpoint, data = null, isDownload = false) {
        console.log('Request:', { method, endpoint, data });
        
        const config = {
            method,
            headers: this.getHeaders(data instanceof FormData),
            credentials: 'include',
        };

        if (method !== 'GET' && data !== null) {
            if (data instanceof FormData) {
                data.append('_token', this.csrfToken);
                config.body = data;
            } else {
                // Ensure data is an object
                const bodyData = data || {};
                bodyData._token = this.csrfToken;
                config.body = JSON.stringify(bodyData);
            }
        }

        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, config);
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                const contentType = response.headers.get('content-type');

                if (contentType && contentType.includes('application/json')) {
                    const jsonResponse = await response.json();
                    const status = response.status;
                    const message = jsonResponse.message || 'Something went wrong';
                    const errors = jsonResponse.errors || {};

                    console.error('Error response:', jsonResponse);

                    if (status === 400 || status === 422) {
                        if (Object.keys(errors).length > 0) {
                            Object.values(errors).forEach(errorMessages => {
                                errorMessages.forEach(errorMessage => {
                                    toastr.error(errorMessage);
                                });
                            });
                        } else {
                            toastr.error(message);
                        }
                    } else if (status === 401) {
                        Swal.fire('Unauthorized', message, 'error');
                    } else if (status === 403) {
                        Swal.fire('Forbidden', message, 'error');
                    } else if (status === 404) {
                        toastr.error(message || 'Resource not found');
                    } else {
                        toastr.error('Something went wrong');
                    }
                    const httpError = new Error(message);
                    httpError.status = status;
                    httpError.errors = errors;
                    throw httpError;
                } else {
                    const textResponse = await response.text();
                    console.error('Non-JSON error response:', textResponse);
                    toastr.error('An error occurred. Please try again.');
                    throw new Error('Non-JSON error');
                }
            }


            if (isDownload) {
                const blob = await response.blob();
                return blob;
            }

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const json = await response.json();
                console.log('Success response (json):', json);
                return json;
            } else {
                const text = await response.text();
                console.log('Success response (text):', text);
                return text; // <-- now edit/show can return HTML
            }

        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }

    get(endpoint) {
        return this.request('GET', endpoint);
    }

    post(endpoint, data, isDownload = false) {
        return this.request('POST', endpoint, data, isDownload);
    }

    put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    }

    delete(endpoint) {
        return this.request('DELETE', endpoint);
    }
}

export default RequestClient;