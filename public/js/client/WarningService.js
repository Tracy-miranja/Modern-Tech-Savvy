
// class WarningService {
//     constructor(requestClient) {
//         this.requestClient = requestClient;
//     }

//     async fetch(data) {
//         try {
//             const response = await this.requestClient.post('/warning/fetch', data);
//             return response.data;
//         } catch (error) {
//             throw { response: error.response || { data: { message: 'Failed to fetch warning data.', errors: [] } } };
//         }
//     }

//     async save(data) {
//         try {
//             const response = await this.requestClient.post('/warning/store', data);
//             Swal.fire('Congratulations!', response.message, 'success');
//             this.handleRedirect(response.data?.redirect_url);
//             return response;
//         } catch (error) {
//             throw { response: error.response || { data: { message: 'Failed to save warning.', errors: [] } } };
//         }
//     }

//     async update(data) {
//         try {
//             const id = data.get('warning_id');
//             const response = await this.requestClient.post(`/warning/${id}/update`, data);
//             toastr.info(response.message, "Success");
//             this.handleRedirect(response.data?.redirect_url);
//             return response;
//         } catch (error) {
//             throw { response: error.response || { data: { message: 'Failed to update warning.', errors: [] } } };
//         }
//     }

//     async edit(data) {
//         try {
//             const response = await this.requestClient.post('/warning/edit', data);
//             return response.data;
//         } catch (error) {
//             throw { response: error.response || { data: { message: 'Failed to edit warning.', errors: [] } } };
//         }
//     }

//     async delete(data) {
//         try {
//             const id = data.warning_id;
//             const response = await this.requestClient.post(`/warning/${id}/destroy`, data);
//             toastr.info(response.message, "Success");
//             return response;
//         } catch (error) {
//             throw { response: error.response || { data: { message: 'Failed to delete warning.', errors: [] } } };
//         }
//     }

//     handleRedirect(route) {
//         if (route) {
//             setTimeout(() => {
//                 window.location.href = route;
//             }, 1500);
//         }
//     }
// }

// export default WarningService;
