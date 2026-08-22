class MandatoryLeavePeriodService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    base() {
        if (!window.businessSlug) {
            throw new Error("businessSlug is missing on window. Set window.businessSlug in the view.");
        }
        return `/business/${window.businessSlug}/mandatory-leave-days`;
    }

    async fetch() {
        const response = await this.requestClient.post(`${this.base()}/fetch`, {});
        return response.data;
    }

    async create() {
        const response = await this.requestClient.post(`${this.base()}/create`, {});
        return response.data;
    }

    async edit(data) {
        const response = await this.requestClient.post(`${this.base()}/edit`, data);
        return response.data;
    }

    async store(data) {
        const response = await this.requestClient.post(`${this.base()}/store`, data);
        return response;
    }

    async update(data) {
        const response = await this.requestClient.post(`${this.base()}/update`, data);
        return response;
    }

    async delete(data) {
        const response = await this.requestClient.post(`${this.base()}/delete`, data);
        return response;
    }
}

export default MandatoryLeavePeriodService;
