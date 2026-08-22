class RoleService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    async fetch(data) {
        try {
            const response = await this.requestClient.post('/roles/fetch', data);
            return response.data;
        } catch (error) {
            console.error('Fetch error:', error);
            throw error;
        }
    }

    async assign(data) {
        try {
            const response = await this.requestClient.post('/roles/assign', data);
            toastr.success(response.message, "Success");
            return response.data;
        } catch (error) {
            console.error('Assign error:', error);
            throw error;
        }
    }

    async modules() {
        try {
            const response = await this.requestClient.get('/roles/modules');
            return response.data;
        } catch (error) {
            console.error('Modules fetch error:', error);
            throw error;
        }
    }

    async store(data) {
        try {
            const response = await this.requestClient.post('/roles/store', data);
            toastr.success(response.message, "Success");
            return response.data;
        } catch (error) {
            console.error('Store role error:', error);
            throw error;
        }
    }

    async edit(roleId) {
        try {
            const response = await this.requestClient.post('/roles/edit', { role_id: roleId });
            return response.data;
        } catch (error) {
            console.error('Edit role error:', error);
            throw error;
        }
    }

    async update(data) {
        try {
            const response = await this.requestClient.post('/roles/update', data);
            toastr.success(response.message, "Success");
            return response.data;
        } catch (error) {
            console.error('Update role error:', error);
            throw error;
        }
    }

    async destroy(roleId) {
        try {
            const response = await this.requestClient.post('/roles/destroy', { role_id: roleId });
            toastr.success(response.message, "Success");
            return response.data;
        } catch (error) {
            console.error('Destroy role error:', error);
            throw error;
        }
    }
}

export default RoleService;