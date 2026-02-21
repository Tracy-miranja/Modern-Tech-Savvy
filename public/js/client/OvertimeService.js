// OvertimeService.js
class OvertimeService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    getBusinessSlug() {
    if (window.__BUSINESS_SLUG) return window.__BUSINESS_SLUG;

    const parts = window.location.pathname.split('/').filter(Boolean);
    const i = parts.indexOf("business");
    const slug = (i !== -1 && parts[i + 1]) ? parts[i + 1] : "";
    window.__BUSINESS_SLUG = slug;
    return slug;
    }

    base() {
        const slug = this.getBusinessSlug();
        if (!slug) throw new Error("Business slug missing for overtime routes.");
        return `/business/${slug}/overtime`;
    }

    async fetch(data) {
        try {
            const response = await this.requestClient.post(`${this.base()}/fetch`, data);
            return response.data;
        } catch (error) {
            throw { response: error.response || { data: { message: "Failed to fetch overtime data.", errors: [] } } };
        }
    }

    async save(data) {
        try {
            const response = await this.requestClient.post(`${this.base()}/store`, data);
            Swal.fire("Success!", response.message || "Saved", "success");
            this.handleRedirect(response.data?.redirect_url);
            return response;
        } catch (error) {
            throw { response: error.response || { data: { message: "Failed to save overtime.", errors: [] } } };
        }
    }

    async update(data) {
        try {
            const response = await this.requestClient.post(`${this.base()}/update`, data);
            toastr.info(response.message || "Updated", "Success");
            this.handleRedirect(response.data?.redirect_url);
            return response;
        } catch (error) {
            throw { response: error.response || { data: { message: "Failed to update overtime.", errors: [] } } };
        }
    }

    async edit(data) {
        try {
            const response = await this.requestClient.post(`${this.base()}/edit`, data);
            return response.data;
        } catch (error) {
            throw { response: error.response || { data: { message: "Failed to edit overtime.", errors: [] } } };
        }
    }

    async delete(data) {
        try {
            const response = await this.requestClient.post(`${this.base()}/destroy`, data);
            toastr.info(response.message || "Deleted", "Success");
            return response;
        } catch (error) {
            throw { response: error.response || { data: { message: "Failed to delete overtime.", errors: [] } } };
        }
    }

    handleRedirect(route) {
        if (route) {
            setTimeout(() => (window.location.href = route), 1500);
        }
    }
}

export default OvertimeService;
