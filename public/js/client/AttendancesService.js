class AttendancesService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

      base() {
    // your routes use {business} parameter; you pass slug in the UI
    return `/business/${window.businessSlug}/attendances`;
  }

    async fetch(data) {
        try {
            const response = await this.requestClient.post('/attendances/fetch', data);
            return response.data;
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async monthly(data) {
        try {
            const response = await this.requestClient.post('/attendances/monthly', data);
            return response.data;
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async clockins(data) {
        try {
            const response = await this.requestClient.post('/attendances/clockins', data);
            return response.data;
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async update(data) {
        try {
            const response = await this.requestClient.post('/attendances/update', data);
            toastr.info(response.message, "Success");
            this.handleRedirect(response.data.redirect_url);
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async edit(data) {
        try {
            const response = await this.requestClient.post(`${this.base()}/edit`, data);
            return response.data;
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async clockIn(data) {
        try {
            const response = await this.requestClient.post('/attendances/clockin', data);
            toastr.success(response.message, "Success");
        } catch (error) {
            console.log(error);
            toastr.error('Employee already clocked in', "Error");
            throw error;
        }
    }


    async clockOut(data) {
        try {
            const response = await this.requestClient.post('/attendances/clockout', data);
            toastr.success(response.message, "Success");
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async delete(data) {
        try {
            const response = await this.requestClient.post('/attendances/delete', data);
            toastr.info(response.message, "Success");
        } catch (error) {
            console.log(error)
            throw error;
        }
    }

    async view(data) {
    const response = await this.requestClient.post(`${this.base()}/view`, data);
    return response.data;
    }


    handleRedirect(route) {
        if (route) {
            setTimeout(() => {
                window.location.href = route;
            }, 1500);
        }
    }
}


export default AttendancesService;
