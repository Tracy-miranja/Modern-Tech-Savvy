class PayrollFormulasService {
    constructor(requestClient) {
        this.requestClient = requestClient;
    }

    async fetch(data) {
        try {
            const response = await this.requestClient.post('/payroll-formulas/fetch', data);
            return response.data;
        } catch (error) {
            throw { response: error.response || { data: { message: 'Failed to fetch formulas.', errors: [] } } };
        }
    }

    async save(data) {
        try {
            const response = await this.requestClient.post('/payroll-formulas/store', data);
            Swal.fire('Congratulations!', response.message, 'success');
            return response;
        } catch (error) {
            throw { response: error.response || { data: { message: 'Failed to save formula.', errors: [] } } };
        }
    }

    async update(data) {
        try {
            const id = data.get('formula_id');
            const response = await this.requestClient.post(`/payroll-formulas/${id}/update`, data);
            toastr.info(response.message, "Success");
            return response;
        } catch (error) {
            throw { response: error.response || { data: { message: 'Failed to update formula.', errors: [] } } };
        }
    }

    async edit(data) {
        try {
            const response = await this.requestClient.post('/payroll-formulas/edit', data);
            return response.data;
        } catch (error) {
            throw { response: error.response || { data: { message: 'Failed to edit formula.', errors: [] } } };
        }
    }

    async delete(data) {
        try {
            const id = data.formula_id;
            const response = await this.requestClient.post(`/payroll-formulas/${id}/destroy`, data);
            toastr.info(response.message, "Success");
            return response;
        } catch (error) {
            throw { response: error.response || { data: { message: 'Failed to delete formula.', errors: [] } } };
        }
    }

    async updateRemittanceDeadline(data) {
        try {
            const response = await this.requestClient.post('/payroll-formulas/remittance-deadline', data);
            return response;
        } catch (error) {
            throw { response: error.response || { data: { message: 'Failed to update remittance deadline.', errors: [] } } };
        }
    }
}

export default PayrollFormulasService;