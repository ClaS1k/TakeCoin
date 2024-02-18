function get_requests() {
    Controller.getRequests().then(data => {
        if (data.is_error) {
            alert(data.message);
            return;
        }

        usersData = data.data;

        let container = document.querySelector(".requests_table");

        let html = `<div class="requests_table_tr">
                    <div class="requests_table_date">Дата</div>
                    <div class="requests_table_user_id">UID</div>
                    <div class="requests_table_amount">Сумма</div>
                    <div class="requests_table_method">Метод</div>
                    <div class="requests_table_data">Примечание</div>
                </div>`;

        usersData.result.forEach((item, index) => {
            html = html + `<div class="requests_table_tr">
                    <div class="requests_table_date">${item.creation_date}</div>
                    <div class="requests_table_user_id">${item.user_id}</div>
                    <div class="requests_table_amount">${item.amount}</div>
                    <div class="requests_table_method">${item.method}</div>
                    <div class="requests_table_data">${item.data}</div>
                    <select class="requests_table_status_selector" onchange="setRequestStatus(${item.id}, this)">
                        <option value='created' ${item.status == "created" ? "selected" : ""}>Создана</option>
                        <option value='in_progress' ${item.status == "in_progress" ? "selected" : ""}>В обработке</option>
                        <option value='accepted' ${item.status == "accepted" ? "selected" : ""}>Завершена</option>
                        <option value='declined' ${item.status == "declined" ? "selected" : ""}>Отклонена</option>
                    </select>
                </div>`;
        });

        container.innerHTML = html;
    });
}

function setRequestStatus(request_id, elem){
    Controller.setRequestStatus(request_id, elem.value).then(data => {
        if (data.is_error) {
            alert(data.message);
            return;
        }
    });
}

window.onload = () => {
    if (!Controller.checkAdminToken()) {
        alert("Необходима авторизация!");
        document.location = "auth.html";
    }

    get_requests();
}