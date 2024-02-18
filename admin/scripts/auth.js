function auth(){
    const username_input = document.querySelector(".auth_form_username_input");
    const password_input = document.querySelector(".auth_form_password_input");

    Controller.authAdmin(username_input.value, password_input.value).then(data => {
        if (data.is_error) {
            const error_message = document.querySelector(".error_message");
            error_message.innerText = data.message;
            return;
        } else {
            document.location = "index.html";
        }
    });
}