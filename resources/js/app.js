import './bootstrap';
// Tambahkan event listener untuk reinisialisasi menu setelah navigasi Livewire
document.addEventListener('livewire:navigated', () => {
    // Reinisialisasi menu
    if (window.Menu) {
        const menuElement = document.querySelector('.menu');
        if (menuElement && menuElement.menuInstance) {
            menuElement.menuInstance.update();
        }
    }
});