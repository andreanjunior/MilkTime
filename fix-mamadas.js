// Corrige o botão "Mais" para exibir corretamente as mamadas escondidas, garantindo compatibilidade com Materialize e navegadores.
// O problema pode ser causado por display inline-flex/flex herdado do Materialize. Força display: list-item ao revelar.
// Também melhora a lógica para garantir que a classe seja aplicada corretamente.

// Substituir o trecho do JS responsável pelo botão "Mais":
document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('select');
    if (window.M && M.FormSelect) M.FormSelect.init(elems);
    setTimeout(function(){
        var elems = document.querySelectorAll('input[type=datetime-local]');
        if (window.M && M.updateTextFields) M.updateTextFields();
    }, 100);
    var btn = document.getElementById('mostrar-mais-mamadas');
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.hidden-mamada').forEach(function(el) {
                el.style.display = 'list-item'; // Garante que <li> volte ao padrão da lista
            });
            btn.style.display = 'none';
        });
    }
});
