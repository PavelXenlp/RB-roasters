<section class="account-page">
        <aside>
            <h1>Личный кабинет</h1>
            <a href="#orders">История заказов</a>
            <a href="#profile">Мои данные</a>
            <a href="#discount">Моя скидка</a>
        </aside>
        <div class="account-content">
            <section id="orders"><h2>История заказов</h2><p>Вы еще не совершили ни одного заказа.</p><a class="button button--small" href="<?= route_url('catalog') ?>">Выбрать кофе</a></section>
            <section id="profile"><h2>Мои данные</h2><div class="form-grid"><input placeholder="ФИО"><input placeholder="Телефон"><input placeholder="Почта"></div></section>
            <section id="discount"><h2>Моя скидка</h2><div class="discount-line"><span>0%</span><span>5%</span><span>10%</span><span>15%</span></div><p>Вы купили на 0 ₽. Условия программы лояльности будут дублироваться здесь.</p></section>
        </div>
    </section>
