function get_users(){
    Controller.getUsers().then(data => {
        if (data.is_error) {
            alert(data.message);
            return;
        }

        usersData = data.data;

        let container = document.querySelector(".users_table");

        let html = `<div class="users_table_tr">
                    <div class="users_table_user_id">ID</div>
                    <div class="users_table_user_username">Никнейм</div>
                    <div class="users_table_user_email">Email</div>
                    <div class="users_table_user_phone">Телефон</div>
                    <div class="users_table_user_balance">Баланс</div>
                </div>`;

        usersData.result.forEach((item, index) => {
            html = html + `<div class="users_table_tr">
                <div class="users_table_user_id">${item.id}</div>
                <div class="users_table_user_username">${item.username}</div>
                <div class="users_table_user_email">${item.email}</div>
                <div class="users_table_user_phone">${item.phone}</div>
                <div class="users_table_user_balance">${item.balance}</div>
                <button class="users_table_user_ban">Забанить</button>
            </div>`;
        });

        container.innerHTML = html;
    });
}

window.onload = () => {
    if(!Controller.checkAdminToken()){
        alert("Необходима авторизация!");
        document.location = "auth.html";
    }

    get_users();
}