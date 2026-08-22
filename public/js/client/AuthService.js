class AuthService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    async login(data) {
        try {
            const response = await this.requestClient.post('/login', data);
            await Swal.fire({
                icon: 'success',
                title: 'Success',
                text: response.message,
                confirmButtonText: 'OK'
            });
            this.handleRedirect(response.data.redirect_url);
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Login failed. Please try again.'
            });
            throw error;
        }
    }

    async register(data) {
        try {
            const response = await this.requestClient.post('/register', data);
            await Swal.fire({
                icon: 'success',
                title: 'Success',
                text: response.message,
                confirmButtonText: 'OK'
            });
            this.handleRedirect(response.data.redirect_url);
        } catch (error) {
            console.log(error);
            throw error;
        }
    }

    async setup(data) {
        try {
            const response = await this.requestClient.post('/setup', data);
            await Swal.fire({
                icon: 'success',
                title: 'Business Registered',
                text: response.message,
                confirmButtonText: 'OK'
            });
            this.handleRedirect(response.data.redirect_url);
        } catch (error) {
            console.log(error);
            throw error;
        }
    }

    async logout(data) {
        try {
            const response = await this.requestClient.post('/logout', data);
            await Swal.fire({
                icon: 'success',
                title: 'Logged Out',
                text: response.message,
                confirmButtonText: 'OK'
            });
            this.handleRedirect(response.data.redirect_url);
        } catch (error) {
            console.log(error);
            throw error;
        }
    }

    // async bsImpersonate(data) {
    //     try {
    //         const response = await this.requestClient.post('/client-businesses/access', data);
    //         await Swal.fire({
    //             icon: 'success',
    //             title: 'Logged In',
    //             text: response.message,
    //             confirmButtonText: 'OK'
    //         });
    //         this.handleRedirect(response.data.redirect_url);
    //     } catch (error) {
    //         console.log(error);
    //         throw error;
    //     }
    // }

    handleRedirect(route) {
        if (route) {
            window.location.href = route;
        }
    }

    handleRedirectToTab(route) {
        if (route) {
            window.open(route, '_blank');
        }
    }
}

export default AuthService;
