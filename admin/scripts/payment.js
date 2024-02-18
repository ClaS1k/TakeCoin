function get_payment_methods(){
    Controller.getPaymentMethods().then(data => {
        if (data.is_error) {
            alert(data.message);
            return;
        }

        usersData = data.data;

        let container = document.querySelector(".methods_list");

        let html = ``;

        usersData.result.forEach((item, index) => {
            html = html + `<div class="payment_method">
                    <img class="payment_method_image" src="${item.image}">
                    <p class="payment_method_name">${item.name}</p>
                    <p class="payment_method_description">${item.description}</p>
                </div>`;
        });

        container.innerHTML = html;
    });
}

function create_payment_method(){
    const method_name = document.querySelector(".method_creation_name");
    const method_description = document.querySelector(".method_creation_description");

    Controller.createPaymentMethod(method_name.value, method_description.value).then(data => {
        if (data.is_error) {
            show_modal(data.message);
            return;
        }

        const form = document.querySelector(".method_creation_image_form");

        let method_id = data.data.result;

        let res = Controller.setMethodPicture(method_id, form);

        if (res.is_error == true) {
            alert("Фото не загружено!");
        }

        setTimeout(() => {
            get_payment_methods();
        }, 1500);
    });
}

window.onload = () => {
    get_payment_methods();
}