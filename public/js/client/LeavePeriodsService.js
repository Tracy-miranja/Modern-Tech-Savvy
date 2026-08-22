class LeavePeriodsService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    async create() {
        try {
            const response = await this.requestClient.post('/leave-periods/create', {});
            return response.data;
        } catch (error) {
            console.log(error)
            throw error;
        }
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

    // The "View" button sends { id }, POSTing to the flat AJAX route
    // (LeavePeriodController::show()) - matches editLeavePeriod()'s
    // { leave_period_slug } / edit() pairing below, not the RESTful
    // GET /{id}/details route (showDetails()), which nothing calls.
    async show(data) {
        try {
            const response = await this.requestClient.post('/leave-periods/show', data);
            return response.data || response;
        } catch (error) {
            console.error('Show leave period error:', error);
            throw error;
        }
    }

    // The "Edit" button sends { leave_period_slug }, POSTing to the flat
    // AJAX route (LeavePeriodController::edit()) for the prefilled form.
    async edit(data) {
        try {
            const response = await this.requestClient.post('/leave-periods/edit', data);
            return response.data || response;
        } catch (error) {
            console.error('Edit leave period error:', error);
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

    async close(leave_period_slug) {
        try {
            const response = await this.requestClient.post('/leave-periods/close', { leave_period_slug });
            toastr.success(response.message || 'Leave period closed.', 'Success');
        } catch (error) {
            console.error('Close leave period error:', error);
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