const dropzone = document.getElementById('dropzone');
const dropzoneFileInput = document.getElementById('dropzone-file');
const imageForm = document.getElementById('imageForm');
const btnForm = document.getElementById('btnForm');

// Prevenir el comportamiento predeterminado del evento dragover
dropzone.addEventListener("dragover", (event) => {
    event.preventDefault();
});

document.addEventListener("paste", (event) => {
    event.preventDefault();
    const clipboardData = event.clipboardData || window.clipboardData;
    const textData = clipboardData.getData('text');
    if (textData) {
        event.target.value = textData;
        return;
    }
    if (clipboardData.items.length === 0) return;
    const file = clipboardData.items[0].getAsFile();
    uploader(file);
});

dropzone.addEventListener("drop", (event) => {
    event.preventDefault();
    const { files } = event.dataTransfer;
    if (files.length === 0) return;
    const file = files[0];
    uploader(file);
});

dropzoneFileInput.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (file) {
        uploader(file);
    }
});

function uploader(file) {
    if (!file) return;
    if (file.size > 25000000) return showToast(false, 'El tamaño máximo es de 25MB.');
    const type = file.type;
    if (!(/image\/(png|webp|jpg|jpeg)/gm.test(type))) return;
    const container = document.getElementById('imagePreview');
    const fileName = file.name;
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onloadend = (result) => {
        container.innerHTML = '';
        const imageElement = document.createElement('img');
        imageElement.src = result.currentTarget.result;
        imageElement.classList.add('w-full', 'h-full', 'object-contain');
        imageElement.alt = fileName;
        imageElement.dataset.name = fileName;
        container.appendChild(imageElement);
        btnForm.disabled = false;
    }
    reader.onerror = (error) => {
        console.error('Error:', error);
    }
}

imageForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    // Obtiene los datos del formulario
    const formData = new FormData(event.target);
    const image = document.getElementById('imagePreview').querySelector('img');
    if (!image || !formData.get('width') || !formData.get('height')) {
        showToast(false, 'No hay imagen o no se ha seleccionado un aspecto.');
        return;
    }
    if (image) {
        formData.append('file', image.src);
        formData.append('name', image.dataset.name);
    }

    // Muestra un skeleton imagen recortada
    const imageCropped = document.getElementById('imageCropped');
    const dimension = ((parseInt(formData.get('width'))/parseInt(formData.get('height'))) > 1.5) ? 'w-full' : 'h-full';
    imageCropped.innerHTML = `<div class="${dimension} aspect-[${formData.get('width')}/${formData.get('height')}] animate-pulse bg-gray-400 dark:bg-gray-700 rounded-lg"></div>`;

    // Limpia las gráficas, etiquetas y el texto
    resetGauges();
    clearTagsAndText();

    // Deshabilita el botón de envío
    btnForm.disabled = true;

    try {
        // Realiza la petición al servidor
        const response = await fetch('php/upload.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.status) {
            btnForm.disabled = false;
            // Muestra los resultados
            displayCroppedImage(data.cropped, `aspect-[${formData.get('width')}/${formData.get('height')}]`);
            updateSafeSearchGauges(data.analysis.safeSearch);
            displayLabels(data.analysis.labels);
            displayText(data.analysis.textDetection);
            showToast(true, 'Imagen analizada correctamente.');
        } else {
            console.log(data);
            showToast(false, 'Error al analizar la imagen.');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast(false, 'Error al analizar la imagen.');
    }
});

function resetGauges() {
    updateGauge('adult', 0);
    updateGauge('medical', 0);
    updateGauge('spoof', 0);
    updateGauge('violence', 0);
    updateGauge('racy', 0);
}

function clearTagsAndText() {
    const tagsContainer = document.getElementById('tags');
    tagsContainer.innerHTML = '<div class="w-1/3 md:w-1/5 h-6 animate-pulse bg-gray-400 dark:bg-gray-700 rounded-full"></div>'.repeat(4);
    
    const textContainer = document.getElementById('text');
    textContainer.innerHTML = '<div class="w-full h-6 animate-pulse bg-gray-400 dark:bg-gray-700 rounded-full mb-2"></div>'.repeat(3);
}

function displayCroppedImage(src, aspect) {
    const imageCropped = document.getElementById('imageCropped');
    imageCropped.innerHTML = '';
    const imageElement = document.createElement('img');
    imageElement.src = src;
    imageElement.classList.add('w-full', 'h-full', 'object-contain', aspect);
    imageCropped.appendChild(imageElement);
}

function updateSafeSearchGauges(safeSearch) {
    updateGauge('adult', safeSearch.adult);
    updateGauge('medical', safeSearch.medical);
    updateGauge('spoof', safeSearch.spoof);
    updateGauge('violence', safeSearch.violence);
    updateGauge('racy', safeSearch.racy);
}

function displayLabels(labels) {
    const tagsContainer = document.getElementById('tags');
    tagsContainer.innerHTML = '';
    labels.forEach(label => {
        const tag = document.createElement('span');
        tag.textContent = label.translate;
        tag.classList.add('text-xs', 'font-bold', 'leading-sm', 'uppercase', 'px-3', 'py-1', 'border', 'bg-blue-200', 'text-blue-700', 'border-blue-700', 'rounded-full');
        tag.title = label.description;
        tagsContainer.appendChild(tag);
    });
}

function displayText(textDetection) {
    const textContainer = document.getElementById('text');
    const description = (textDetection.length > 0) ? textDetection[0].description : 'No se ha detectado texto en la imagen.';
    textContainer.innerHTML = '';
    textContainer.innerHTML = description;
}

function updateGauge(type, score) {
    // Obtiene el gráfico de progreso
    const gauge = document.getElementById(`${type}Gauge`);
    // Calcula el porcentaje de progreso
    const porcentaje = 100 - (score * 20);
    const degree = 180 * (porcentaje / 100);
    // Establece el color del gráfico de progreso
    let color = '#5cb85c';
    if (porcentaje <= 60) {
        color = '#f0ad4e';
    }
    if (porcentaje <= 40) {
        color = '#d9534f';
    }
    // Actualiza el progreso del gráfico
    gauge.style.setProperty('--rotation', `${degree}deg`);
    gauge.style.setProperty('--color', color);
}

function showToast(isSuccess, message) {
    const toast = document.getElementById('toast');
    const tostMessage = document.getElementById('tostMessage');

    const color = isSuccess ? 'bg-green-600' : 'bg-red-600';

    // Muestra el toast
    tostMessage.textContent = message;
    toast.classList.remove('hidden');
    toast.classList.remove('opacity-0');
    toast.classList.remove('bg-red-600');
    toast.classList.remove('bg-green-600');
    toast.classList.add('opacity-100');
    toast.classList.add(color);

    // Después de 3 segundos, oculta el toast
    setTimeout(() => {
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0');

        // Ocultarlo completamente después de la transición
        setTimeout(() => {
            toast.classList.add('hidden');
        }, 300); // Tiempo de la animación de opacidad
    }, 3000); // El toast desaparece después de 3 segundos
}
