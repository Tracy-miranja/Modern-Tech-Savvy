class LeaveEntitlementsService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    async fetch(data) {
        try {
            const response = await this.requestClient.post('/leave-entitlements/fetch', data);
            return response.data;
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

// leaveentitlementservice.js
    async show(slug) {
    try {
        // send slug in the body (matches route & controller)
        return await this.requestClient.post('/leave-entitlements/show', { slug });
    } catch (e) { console.log(e); throw e; }
    }


    async edit(data) {
    try {
        return await this.requestClient.post('/leave-entitlements/edit', data); // returns EDIT FORM HTML now
    } catch (e) { console.log(e); throw e; }
    }

    async update(formDataOrPlain) {
    try {
        return await this.requestClient.post('/leave-entitlements/update', formDataOrPlain);
    } catch (e) { console.log(e); throw e; }
    }


    async save(data) {
        try {
            const response = await this.requestClient.post('/leave-entitlements/store', data);
            toastr.success(response.message, "Success");
            return response;
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async delete(data) {
        try {
            const response = await this.requestClient.post('/leave-entitlements/delete', data);
            toastr.info(response.message, "Success");
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async adjust(data) {
        try {
            const response = await this.requestClient.post('/leave-entitlements/adjust', data);
            toastr.success(response.message, "Success");
            return response;
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async processCarryover(data) {
        try {
            const response = await this.requestClient.post('/leave-entitlements/process-carryover', data);
            toastr.success(response.message, "Success");
            return response;
        } catch (error) {
            console.log(error)
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

export default LeaveEntitlementsService;