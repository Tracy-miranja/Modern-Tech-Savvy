class WorkScheduleService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    base() {
        if (!window.businessSlug) {
            throw new Error("businessSlug is missing on window. Set window.businessSlug in the view.");
        }
        return `/business/${window.businessSlug}/work-schedules`;
    }

    async fetch(data) {
        const response = await this.requestClient.post(`${this.base()}/fetch`, data);
        return response.data;
    }

    async save(data) {
        const response = await this.requestClient.post(`${this.base()}/store`, data);
        toastr.success(response.message, "Success");
        return response;
    }

    async createForm() {
        const response = await this.requestClient.post(`${this.base()}/create-form`, {});
        return response.data;
    }

    async edit(data) {
        const response = await this.requestClient.post(`${this.base()}/edit`, data);
        return response.data;
    }

    async update(data) {
        const response = await this.requestClient.post(`${this.base()}/update`, data);
        toastr.info(response.message, "Success");
        return response;
    }

    async delete(data) {
        const response = await this.requestClient.post(`${this.base()}/delete`, data);
        toastr.info(response.message, "Success");
        return response;
    }

    async getScheduleInfo(data) {
        const response = await this.requestClient.post(`${this.base()}/schedule-info`, data);
        return response.data;
    }

    async activate(data) {
        const response = await this.requestClient.post(`${this.base()}/activate`, data);
        toastr.success(response.message, "Success");
        return response;
    }

    async bulkStore(data) {
        const response = await this.requestClient.post(`${this.base()}/bulk-store`, data);
        toastr.success(response.message, "Success");
        return response;
    }

}



export default WorkScheduleService;
