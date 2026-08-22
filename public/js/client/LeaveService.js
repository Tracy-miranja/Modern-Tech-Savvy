class LeaveService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    async fetch(data) {
        try {
            const response = await this.requestClient.post('/leave/fetch', data);
            return response.data;
        } catch (error) {
            console.error('Fetch error:', error);
            this.handleError(error, 'Failed to fetch leave requests');
            throw error;
        }
    }

    async update(data) {
        try {
            const response = await this.requestClient.post('/leave/update', data);
            toastr.success(response.message, "Success");
            this.handleRedirect(response.data.redirect_url);
        } catch (error) {
            console.error('Update error:', error);
            this.handleError(error, 'Failed to update leave request');
            throw error;
        }
    }

    async edit(data) {
        try {
            const response = await this.requestClient.post('/leave/edit', data);
            return response.data;
        } catch (error) {
            console.error('Edit error:', error);
            this.handleError(error, 'Failed to load edit form');
            throw error;
        }
    }

    async save(data) {
        try {
            const response = await this.requestClient.post('/leave/store', data);
            toastr.success(response.message, "Success");
        } catch (error) {
            console.error('Save error:', error);
            this.handleError(error, 'Failed to save leave request');
            throw error;
        }
    }

    async status(data) {
        try {
            const response = await this.requestClient.post('/leave/status', data);
            toastr.success(response.message, "Success");
        } catch (error) {
            console.error('Status error:', error);
            this.handleError(error, 'Failed to update leave status');
            throw error;
        }
    }

    async cancel(data) {
        try {
            const response = await this.requestClient.post('/leave/cancel', data);
            toastr.success(response.message, "Success");
        } catch (error) {
            console.error('Cancel error:', error);
            this.handleError(error, 'Failed to cancel leave request');
            throw error;
        }
    }

    async delete(data) {
        try {
            const response = await this.requestClient.post('/leave/delete', data);
            toastr.success(response.message, "Success");
        } catch (error) {
            console.error('Delete error:', error);
            this.handleError(error, 'Failed to delete leave request');
            throw error;
        }
    }

    handleError(error, defaultMessage) {
        let errorMessage = defaultMessage;
        
        if (error.response && error.response.data) {
            if (error.response.data.message) {
                errorMessage = error.response.data.message;
            } else if (typeof error.response.data === 'string') {
                errorMessage = error.response.data;
            }
        } else if (error.message) {
            errorMessage = error.message;
        }

        // Show user-friendly error messages
        toastr.error(errorMessage, "Error");
    }

    handleRedirect(route) {
        if (route) {
            setTimeout(() => {
                window.location.href = route;
            }, 1500);
        }
    }
}

export default LeaveService;