layui.use(['form', 'layer', 'laydate', 'element'], function () {
    const form = layui.form;
    const layer = layui.layer;
    const laydate = layui.laydate;

    form.render();
    document.querySelectorAll('.date-picker').forEach((item) => laydate.render({ elem: item, type: 'date' }));
    document.querySelectorAll('.month-picker').forEach((item) => laydate.render({ elem: item, type: 'month' }));
    document.querySelectorAll('.time-picker').forEach((item) => laydate.render({ elem: item, type: 'time', format: 'HH:mm' }));

    document.querySelectorAll('.js-delete-form').forEach((formElement) => {
        formElement.addEventListener('submit', function (event) {
            event.preventDefault();
            layer.confirm('确认删除这条记录吗？', { icon: 3, title: '删除确认' }, function () {
                formElement.submit();
            });
        });
    });

    document.querySelectorAll('.app-message,.app-errors').forEach((item) => {
        const message = item.dataset.message;
        if (message) {
            layer.msg(message, { icon: item.classList.contains('app-errors') ? 2 : 1 });
        }
    });

    const toggle = document.getElementById('themeToggle');
    const currentTheme = localStorage.getItem('admin-theme');
    if (currentTheme === 'dark') {
        document.body.classList.add('dark');
    }
    if (toggle) {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('dark');
            localStorage.setItem('admin-theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });
    }
});
