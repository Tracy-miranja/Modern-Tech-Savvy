class LeavePeriodsService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    async fetch(data) {
        try {
            const response = await this.requestClient.post('/leave-periods/fetch', data);
            // Return the data property from response
            return response.data || response;
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async update(data) {
        try {
            const response = await this.requestClient.post('/leave-periods/update', data);
            toastr.info(response.message, "Success");
            if (response.data?.redirect_url) {
                this.handleRedirect(response.data.redirect_url);
            }
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async fetchOne(leavePeriodId) {
        try {
            const response = await this.requestClient.get(`/leave-periods/${leavePeriodId}/json`);
            // Return just the data, not wrapped
            return response;
        } catch (error) {
            console.error('Fetch leave period error:', error);
            throw error;
        }
    }

    async show(leavePeriodId) {
        try {
            const response = await this.requestClient.get(`/leave-periods/${leavePeriodId}/details`);
            // Return the data property which contains the HTML
            return response.data || response;
        } catch (error) {
            console.error('Show leave period error:', error);
            throw error;
        }
    }

    async save(data) {
        try {
            const response = await this.requestClient.post('/leave-periods/store', data);
            toastr.success(response.message, "Success");
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async delete(leave_period_slug) {
        try {
            const response = await this.requestClient.post('/leave-periods/delete', { leave_period_slug });
            toastr.info(response.message || 'Leave period deleted.', 'Success');
        } catch (error) {
            console.error('Delete leave period error:', error);
            throw error;
        }
    }

    handleRedirect(route) {
        if (route) {
            setTimeout(() => {
                window.location.href = route;
            }, 1500);
        }
    }
}

export default LeavePeriodsService;
