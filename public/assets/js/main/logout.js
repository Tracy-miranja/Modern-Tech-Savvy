import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import AuthService from "/js/client/AuthService.js";

const requestClient = new RequestClient();
const authService = new AuthService(requestClient);

window.logout = async function (btn) {
    const data = {};
    try {
        await authService.logout(data);
    } finally {
    }
};

// window.bsImpersonate = async function (btn) {
//     btn = $(btn);
//     btn_loader(btn, true);
//     const business_slug = btn.data("business");
//     const data = { business_slug: business_slug };
//     try {
//         await authService.bsImpersonate(data);
//     } finally {
//         btn_loader(btn, false);
//     }
// };
