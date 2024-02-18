function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function deleteCookie(name) {
    if (getCookie(name)) {
        document.cookie = name + "=" + ";expires=Thu, 01 Jan 1970 00:00:01 GMT";
    }
}

function generate_string(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
        counter += 1;
    }
    return result;
}

class TakeCoinController {
    constructor(){
        this.address = "http://takecoin.ru/api/"
    }

    async authAdmin(username, password) {
        let data = {
            "username": username,
            "password": password
        }

        let url = `${this.address}auth/admin`;

        let result = await fetch(url, {
            method: "POST",
            body: JSON.stringify(data),
            headers: {
                "Content-Type": "application/json",
            }
        }).then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try {
                        let error_data = JSON.parse(text);
                        return { "is_error": true, "message": error_data.message }
                    } catch {
                        return { "is_error": true, "message": "Неизвестная ошибка!" };
                    }
                });
            }
            else {
                res.text().then(text => {
                    let json = JSON.parse(text);

                    document.cookie = "takecoin_admin_token=" + json.result.token;
                });

                return { "is_error": false };
            }
        });

        return result;
    }

    async getUsers() {
        let url = `${this.address}users`;

        return await fetch(url, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "auth": getCookie("takecoin_admin_token")
            }
        }).then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try {
                        let error_data = JSON.parse(text);
                        return { "is_error": true, "message": error_data.message }
                    } catch {
                        return { "is_error": true, "message": "Неизветная ошибка!" };
                    }
                });
            } else {
                return res.text().then(text => {
                    let json = JSON.parse(text);

                    return { "is_error": false, "data": json };
                });
            }
        });
    }

    async getRequests(){
        let url = `${this.address}payment/requests`;

        return await fetch(url, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "auth": getCookie("takecoin_admin_token")
            }
        }).then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try {
                        let error_data = JSON.parse(text);
                        return { "is_error": true, "message": error_data.message }
                    } catch {
                        return { "is_error": true, "message": "Неизветная ошибка!" };
                    }
                });
            } else {
                return res.text().then(text => {
                    let json = JSON.parse(text);

                    return { "is_error": false, "data": json };
                });
            }
        });
    }

    async setRequestStatus(request_id, status) {
        let url = `${this.address}payment/requests/update`;

        let data = {
            "request_id": request_id,
            "status": status
        }

        let result = await fetch(url, {
            method: "POST",
            body: JSON.stringify(data),
            headers: {
                "Content-Type": "application/json",
                "auth": getCookie("takecoin_admin_token")
            }
        }).then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try {
                        let error_data = JSON.parse(text);
                        return { "is_error": true, "message": error_data.message }
                    } catch {
                        return { "is_error": true, "message": "Неизвестная ошибка!" };
                    }
                });
            }
            else {
                return res.text().then(text => {
                    return { "is_error": false };
                });
            }
        });

        return result;
    }

    async getPaymentMethods() {
        let url = `${this.address}payment/methods`;

        return await fetch(url, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "auth": getCookie("takecoin_admin_token")
            }
        }).then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try {
                        let error_data = JSON.parse(text);
                        return { "is_error": true, "message": error_data.message }
                    } catch {
                        return { "is_error": true, "message": "Неизветная ошибка!" };
                    }
                });
            } else {
                return res.text().then(text => {
                    let json = JSON.parse(text);

                    return { "is_error": false, "data": json };
                });
            }
        });
    }

    async createPaymentMethod(name, description) {
        let url = `${this.address}payment/methods/create`;

        let data = {
            "name": name,
            "description": description
        }

        let result = await fetch(url, {
            method: "POST",
            body: JSON.stringify(data),
            headers: {
                "Content-Type": "application/json",
                "auth": getCookie("takecoin_admin_token")
            }
        }).then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try {
                        let error_data = JSON.parse(text);
                        return { "is_error": true, "message": error_data.message }
                    } catch {
                        return { "is_error": true, "message": "Неизвестная ошибка!" };
                    }
                });
            }
            else {
                return res.text().then(text => {
                    let json = JSON.parse(text);

                    return { "is_error": false, "data": json };
                });
            }
        });

        return result;
    }

    async setMethodPicture(method_id, form) {
        const data = new FormData(form);

        let url = `${this.address}payment/methods/picture/${method_id}`;

        let result = await fetch(url, {
            method: "POST",
            body: data,
            headers: {
                "auth": getCookie("takecoin_admin_token")
            }
        }).then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try {
                        let error_data = JSON.parse(text);
                        return { "is_error": true, "message": error_data.message }
                    } catch {
                        return { "is_error": true, "message": "Неизвестная ошибка" };
                    }
                });
            }
            else {
                return { "is_error": false };
            }
        });

        return result;
    }

    checkAdminToken() {
        if (!getCookie("takecoin_admin_token")) {
            return false;
        } else {
            return true;
        }
    }
}

const Controller = new TakeCoinController();