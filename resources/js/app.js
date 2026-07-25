import 'preline';

const initializePreline = () => {
    window.HSStaticMethods?.autoInit();
};

document.addEventListener('DOMContentLoaded', initializePreline);
document.addEventListener('livewire:navigated', initializePreline);
