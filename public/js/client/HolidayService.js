// HolidayService.js
class HolidayService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    base() {
        if (!window.businessSlug) {
            throw new Error("businessSlug is missing on window. Set window.businessSlug in the view.");
        }
        return `/business/${window.businessSlug}/holidays`;
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

    async checkHoliday(data) {
        const response = await this.requestClient.post(`${this.base()}/check`, data);
        return response.data;
    }

    async availableCountries(locationId) {
        const query = locationId ? `?location_id=${encodeURIComponent(locationId)}` : '';
        const response = await this.requestClient.get(`${this.base()}/countries${query}`);
        return response.data;
    }

    async importFromApi(data) {
        const response = await this.requestClient.post(`${this.base()}/import`, data);
        toastr.success(response.message, "Success");
        return response;
    }
}

export default HolidayService;
