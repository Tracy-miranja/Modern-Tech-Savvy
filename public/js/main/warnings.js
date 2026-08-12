// // warnings.js
// import { btn_loader } from "/js/client/config.js";
// import RequestClient from "/js/client/RequestClient.js";
// import WarningService from "/js/client/WarningService.js";

// const requestClient = new RequestClient();
// const warningService = new WarningService(requestClient);

// window.getWarnings = async function (page = 1) {
//     try {
//         let data = { page: page };
//         const response = await warningService.fetch(data);
//         $("#warningsContainer").html(response.html);
//         $("#warningCount").text(response.count); // Update the badge
//     } catch (error) {
//         console.error("Error loading warnings:", error);
//         Swal.fire('Error!', error.response?.data?.message || 'Failed to load warnings.', 'error');
//     }
// };

// window.saveWarning = async function (btn) {
//     btn = $(btn);
//     btn_loader(btn, true);

//     let form = $('#warningForm');
//     let formData = new FormData(document.getElementById("warningForm"));

//     try {
//         if (formData.has("warning_id")) {
//             await warningService.update(formData);
//             Swal.fire('Success!', 'Warning updated successfully.', 'success');
//         } else {
//             await warningService.save(formData);
//             Swal.fire('Success!', 'Warning issued successfully.', 'success');
//         }
//         form[0].reset();
//         $('#warningFormContainer').html(await warningService.edit({}));
//         getWarnings();
//     } catch (error) {
//         console.error("Error saving warning:", error);
//         Swal.fire('Error!', error.response?.data?.message || 'Failed to save warning.', 'error');
//     } finally {
//         btn_loader(btn, false);
//     }
// };

// window.editWarning = async function (btn) {
//     btn = $(btn);
//     const warning = btn.data("warning");
//     const data = { warning_id: warning };

//     try {
//         const form = await warningService.edit(data);
//         $('#warningFormContainer').html(form);
//     } catch (error) {
//         console.error("Error editing warning:", error);
//         Swal.fire('Error!', error.response?.data?.message || 'Failed to load edit form.', 'error');
//     }
// };

// window.deleteWarning = async function (btn) {
//     btn = $(btn);
//     btn_loader(btn, true);

//     const warning = btn.data("warning");
//     const data = { warning_id: warning };

//     Swal.fire({
//         title: "Are you sure?",
//         text: "You won't be able to revert this!",
//         icon: "warning",
//         showCancelButton: true,
//         confirmButtonColor: "#068f6d",
//         cancelButtonColor: "#d33",
//         confirmButtonText: "Yes, delete it!",
//     }).then(async (result) => {
//         if (result.isConfirmed) {
//             try {
//                 await warningService.delete(data);
//                 Swal.fire('Deleted!', 'Warning deleted successfully.', 'success');
//                 getWarnings();
//             } catch (error) {
//                 console.error("Error deleting warning:", error);
//                 Swal.fire('Error!', error.response?.data?.message || 'Failed to delete warning.', 'error');
//             } finally {
//                 btn_loader(btn, false);
//             }
//         } else {
//             btn_loader(btn, false);
//         }
//     });
// };

// document.addEventListener('DOMContentLoaded', () => {
//     getWarnings();
// });
