<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="Image Processor">
    <title>Image Processor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body class="bg-gray-100 dark:bg-gray-900 min-h-svh">
    <header>
        <h1 class="text-center text-xl font-semibold text-gray-900 dark:text-white my-4">Image Processor</h1>
    </header>
    <main class="container flex flex-wrap items-center justify-center mx-auto px-4 md:px-8">
        <section class="w-full flex flex-col md:flex-row items-center justify-center gap-8 my-4">
            <form id="imageForm"
                class="w-full md:w-1/2 h-[27rem] flex flex-col justify-around bg-gray-300 dark:bg-gray-800 rounded-lg p-4 md:p-6 gap-4 md:gap-6">
                <div class="flex flex-col gap-2">
                    <label for="width" class="text-gray-900 dark:text-white font-semibold">Relación de aspecto</label>
                    <div class="flex gap-2">
                        <input type="number" id="width" name="width"
                            class="w-full p-2 border border-gray-300 rounded-lg text-center" step="1" min="1" value="16"
                            placeholder="Ancho" />
                        <input type="number" id="height" name="height"
                            class="w-full p-2 border border-gray-300 rounded-lg text-center" step="1" min="1" value="9"
                            placeholder="Alto" />
                    </div>
                </div>
                <div class="w-full">
                    <label id="dropzone" for="dropzone-file"
                        class="flex flex-col items-center justify-center w-full border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                            </svg>
                            <p class="mb-2 text-sm text-center text-gray-500 dark:text-gray-400">
                                Haz click aquí, arrastra o pega la imagen
                            </p>
                            <p class="text-xs text-center text-gray-500 dark:text-gray-400">PNG, JPG o WEBP</p>
                        </div>
                        <input id="dropzone-file" type="file" class="hidden"
                            accept="image/png,image/webp,image/jpg,image/jpeg" />
                    </label>
                </div>
                <!-- <div class="flex flex-col gap-2">
                    <label for="quality" class="text-gray-900 dark:text-white font-semibold">Calidad</label>
                    <input id="quality" type="range" name="quality" class="w-full" min="1" max="100" value="100" />
                </div> -->
                <!-- <div class="flex flex-col gap-2">
                    <label for="output" class="text-gray-900 dark:text-white font-semibold">Formato de salida</label>
                    <select id="output" name="output" class="w-full p-2 border border-gray-300 rounded-lg">
                        <option value="png">PNG</option>
                        <option value="jpeg">JPEG</option>
                        <option value="webp">WEBP</option>
                    </select>
                </div> -->
                <div class="flex flex-col gap-2">
                    <button id="btnForm" type="submit"
                        class="w-full p-2 text-white bg-blue-500 rounded-lg disabled:opacity-75 disabled:cursor-not-allowed"
                        disabled>Recortar y analizar</button>
                </div>
            </form>
            <div id="imagePreview"
                class="w-full md:w-1/2 h-[27rem] bg-gray-300 dark:bg-gray-800 rounded-lg overflow-hidden flex items-center justify-center p-4">
                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                    image preview
                </p>
            </div>
        </section>

        <section class="w-full flex flex-col-reverse md:flex-row items-center justify-center gap-8 my-4">
            <div id="imageAnalyze"
                class="w-full md:w-1/2 h-auto md:h-[27rem] bg-gray-300 dark:bg-gray-800 rounded-lg overflow-hidden p-4 md:p-6 overflow-y-auto">
                <!-- Safe score -->
                <p class="text-lg font-bold text-gray-500 dark:text-gray-400 my-4">
                    Puntuación segura
                </p>
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <div id="adultGauge" class="gauge" class="bg-white dark:bg-"
                        style="--rotation:0deg; --color:#5cb85c;">
                        <div class="percentage"></div>
                        <div class="mask bg-gray-300 dark:bg-gray-800"></div>
                        <span class="value text-sm text-gray-900 dark:text-white">Adultos</span>
                    </div>

                    <div id="medicalGauge" class="gauge" class="bg-white dark:bg-"
                        style="--rotation:0deg; --color:#5cb85c;">
                        <div class="percentage"></div>
                        <div class="mask bg-gray-300 dark:bg-gray-800"></div>
                        <span class="value text-sm text-gray-900 dark:text-white">Medico</span>
                    </div>

                    <div id="spoofGauge" class="gauge" class="bg-white dark:bg-"
                        style="--rotation:0deg; --color:#5cb85c;">
                        <div class="percentage"></div>
                        <div class="mask bg-gray-300 dark:bg-gray-800"></div>
                        <span class="value text-sm text-gray-900 dark:text-white">Parodia</span>
                    </div>

                    <div id="violenceGauge" class="gauge" class="bg-white dark:bg-"
                        style="--rotation:0deg; --color:#5cb85c;">
                        <div class="percentage"></div>
                        <div class="mask bg-gray-300 dark:bg-gray-800"></div>
                        <span class="value text-sm text-gray-900 dark:text-white">Violencia</span>
                    </div>

                    <div id="racyGauge" class="gauge" class="bg-white dark:bg-"
                        style="--rotation:0deg; --color:#5cb85c;">
                        <div class="percentage"></div>
                        <div class="mask bg-gray-300 dark:bg-gray-800"></div>
                        <span class="value text-sm text-gray-900 dark:text-white">Racista</span>
                    </div>
                </div>

                <!-- Tags -->
                <p class="text-lg font-bold text-gray-500 dark:text-gray-400 my-4">
                    Etiquetas
                </p>
                <div id="tags" class="flex flex-wrap gap-2 mt-4">
                </div>

                <!-- Text Detection -->
                <p class="text-lg font-bold text-gray-500 dark:text-gray-400 my-4">
                    Texto detectado
                </p>
                <div id="textDetection" class="mt-4">
                    <p id="text" class="text-xs text-gray-500 dark:text-gray-400"></p>
                </div>
            </div>
            <div id="imageCropped"
                class="w-full md:w-1/2 h-[27rem] bg-gray-300 dark:bg-gray-800 rounded-lg overflow-hidden flex items-center justify-center p-4">
                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                    image cropped
                </p>
            </div>
        </section>

    </main>

    <div id="toast" class="fixed bottom-10 right-10 text-white p-4 rounded-lg shadow-lg hidden transition-opacity duration-300 opacity-0">
        <div class="flex items-center">
            <span id="tostMessage" class="font-semibold">Error: Algo salió mal!</span>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>

</html>