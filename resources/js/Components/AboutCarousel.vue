<template>
    <div class="about-carousel">
        <!-- Flecha izquierda -->
        <button class="carousel-arrow carousel-arrow-left" @click="previousCard" @mousedown="setArrowPressed('left')" @mouseup="setArrowPressed(null)" @mouseleave="setArrowPressed(null)">
            <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 27 27" fill="none">
                <path d="M24.5425 13.5209C24.5425 7.63938 19.775 2.87193 13.8935 2.87193C8.01206 2.87193 3.24461 7.63937 3.24461 13.5209C3.24461 19.4024 8.01206 24.1698 13.8935 24.1698C19.775 24.1698 24.5425 19.4024 24.5425 13.5209ZM12.5781 17.9224C12.7317 17.7688 12.8179 17.5606 12.8179 17.3436C12.8179 17.1265 12.7317 16.9183 12.5781 16.7647L9.15344 14.34L17.0064 14.34C17.2237 14.34 17.432 14.2537 17.5856 14.1001C17.7393 13.9465 17.8256 13.7381 17.8256 13.5209C17.8256 13.3036 17.7393 13.0953 17.5856 12.9416C17.432 12.788 17.2237 12.7017 17.0064 12.7017L9.15344 12.7017L12.5781 10.277C12.6586 10.202 12.7232 10.1116 12.7679 10.0111C12.8127 9.91064 12.8368 9.80217 12.8387 9.69219C12.8407 9.5822 12.8204 9.47295 12.7792 9.37095C12.738 9.26895 12.6767 9.1763 12.5989 9.09851C12.5211 9.02073 12.4285 8.9594 12.3265 8.91821C12.2245 8.87701 12.1152 8.85677 12.0052 8.85872C11.8953 8.86066 11.7868 8.88473 11.6863 8.9295C11.5858 8.97427 11.4954 9.03882 11.4204 9.1193L7.59769 12.942C7.44429 13.0956 7.35809 13.3038 7.35809 13.5209C7.35809 13.7379 7.44429 13.9461 7.59769 14.0997L11.4204 17.9224C11.574 18.0758 11.7822 18.162 11.9993 18.162C12.2163 18.162 12.4245 18.0758 12.5781 17.9224Z" :fill="arrowPressed === 'left' ? '#FABD4F' : 'none'" :stroke="arrowPressed === 'left' ? 'none' : '#FABD4F'" stroke-width="1.6383"/>
            </svg>
        </button>

        <!-- Contenedor del carousel -->
        <div class="carousel-container">
            <div class="carousel-track" :style="{ transform: `translateX(-${currentIndex * 25}%)` }">
                <!-- Propósito -->
                <div class="carousel-card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 27 27" fill="none">
                            <path d="M20.0536 10.0454C20.0536 11.7834 19.3632 13.4502 18.1343 14.6792C16.9053 15.9081 15.2385 16.5986 13.5005 16.5986C11.7624 16.5986 10.0956 15.9081 8.86665 14.6792C7.63769 13.4502 6.94727 11.7834 6.94727 10.0454C6.94727 8.30736 7.63769 6.64053 8.86665 5.41157C10.0956 4.18261 11.7624 3.49219 13.5005 3.49219C15.2385 3.49219 16.9053 4.18261 18.1343 5.41157C19.3632 6.64053 20.0536 8.30736 20.0536 10.0454Z" stroke="white" stroke-width="1.6383" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9.13126 14.9297L8.03906 21.9634C8.03906 22.8481 9.98536 23.4226 10.7117 23.0272L13.0118 21.7745C13.1615 21.6925 13.3294 21.6495 13.5001 21.6495C13.6707 21.6495 13.8386 21.6925 13.9883 21.7745L16.2884 23.0272C17.0148 23.4226 18.961 22.847 18.961 21.9624L17.8688 14.9297" stroke="white" stroke-width="1.6383" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="card-content">
                        <div class="card-title">Propósito</div>
                        <div class="card-description">
                            <span class="bold-text">Inspirar</span> a más vivir la aventura, explorar y fortalecer el compañerismo en cada viaje.
                        </div>
                    </div>
                </div>

                <!-- Misión -->
                <div class="carousel-card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 27 27" fill="none">
                            <path d="M8.85785 15.3445L11.4791 17.9658M8.85785 15.3445C8.85785 15.3445 10.4055 11.4759 12.6085 8.79129M8.85785 15.3445C3.15767 13.9192 8.27899 8.79129 12.6085 8.79129M11.4791 17.9658C11.4791 17.9658 15.3477 16.4181 18.0323 14.2151M11.4791 17.9658C12.9044 23.6648 18.0323 18.5446 18.0323 14.2151M12.6085 8.79129C14.8365 6.07608 18.5096 4.85938 21.9642 4.85938C21.9642 8.314 20.7475 11.986 18.0323 14.2151M16.1756 10.5465L16.9478 9.77427M6.42116 18.3218C5.38138 19.1967 5.03516 21.7939 5.03516 21.7939C5.03516 21.7939 7.62585 21.4466 8.49852 20.4046C8.99001 19.8214 8.98345 18.9258 8.43626 18.3841C8.16573 18.1298 7.8116 17.9831 7.44049 17.9716C7.06938 17.9602 6.70687 18.0847 6.42116 18.3218Z" stroke="white" stroke-width="1.6383" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="card-content">
                        <div class="card-title">Misión</div>
                        <div class="card-description">
                            <span class="bold-text">Crear experiencias</span> únicas de aprendizaje y entretención a través de programas y productos innovadores.
                        </div>
                    </div>
                </div>

                <!-- Valores -->
                <div class="carousel-card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 27 27" fill="none">
                            <path d="M22.7842 17.4707V9.16996C22.7837 8.80663 22.6883 8.44973 22.5074 8.13461C22.3266 7.81948 22.0666 7.55705 21.7531 7.37329L14.5315 3.22294C14.2183 3.04109 13.8626 2.94531 13.5005 2.94531C13.1383 2.94531 12.7826 3.04109 12.4695 3.22294L5.24783 7.37329C4.93366 7.55621 4.67305 7.81846 4.4921 8.13378C4.31116 8.44909 4.21622 8.80641 4.2168 9.16996V17.4729C4.21751 17.836 4.31299 18.1927 4.49382 18.5076C4.67465 18.8225 4.93456 19.0848 5.24783 19.2684L12.4695 23.4188C12.7826 23.6006 13.1383 23.6964 13.5005 23.6964C13.8626 23.6964 14.2183 23.6006 14.5315 23.4188L21.7531 19.2684C22.0675 19.0854 22.3282 18.823 22.5091 18.5075C22.6901 18.1919 22.7849 17.8344 22.7842 17.4707Z" stroke="white" stroke-width="1.6383" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M11.6337 10.5898C11.3782 10.5897 11.1252 10.6399 10.8891 10.7376C10.653 10.8353 10.4386 10.9786 10.2579 11.1593C10.0772 11.3399 9.93396 11.5544 9.83625 11.7905C9.73855 12.0265 9.68833 12.2796 9.68848 12.5351C9.68848 14.6856 13.3746 17.143 13.3746 17.143C13.3746 17.143 17.0608 14.6856 17.0608 12.5351C17.0608 11.2037 16.1903 10.5898 15.1156 10.5898C14.3533 10.5898 13.6936 11.0267 13.3746 11.6657C13.2132 11.3422 12.9647 11.0702 12.6572 10.8801C12.3496 10.6901 11.9952 10.5896 11.6337 10.5898Z" stroke="white" stroke-width="1.6383" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="card-content">
                        <div class="card-title">Valores</div>
                        <div class="card-description">
                            1) <span class="bold-text">Integridad</span> - lo que somos<br>
                            2) <span class="bold-text">Trascendencia</span> - el legado<br>
                            3) <span class="bold-text">Excelencia</span> - el día a día
                        </div>
                    </div>
                </div>

                <!-- Sostenibilidad -->
                <div class="carousel-card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 27 27" fill="none">
                            <path d="M23.3295 13.322C23.3295 14.6128 23.0752 15.8911 22.5812 17.0837C22.0873 18.2763 21.3632 19.3599 20.4504 20.2727C19.5376 21.1855 18.454 21.9095 17.2614 22.4035C16.0688 22.8975 14.7906 23.1518 13.4997 23.1518C12.2088 23.1518 10.9306 22.8975 9.73801 22.4035C8.54541 21.9095 7.46178 21.1855 6.549 20.2727C5.63622 19.3599 4.91216 18.2763 4.41817 17.0837C3.92418 15.8911 3.66992 14.6128 3.66992 13.322C3.66992 10.715 4.70556 8.21471 6.549 6.37127C8.39244 4.52782 10.8927 3.49219 13.4997 3.49219C16.1067 3.49219 18.607 4.52782 20.4504 6.37127C22.2939 8.21471 23.3295 10.715 23.3295 13.322Z" stroke="white" stroke-width="1.6383" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14.5915 3.54688C13.8985 4.12938 13.3612 4.87504 13.0279 5.71675C12.6946 6.55846 12.5758 7.46982 12.6822 8.36884C12.7886 9.26787 13.1168 10.1263 13.6373 10.867C14.1578 11.6077 14.8543 12.2074 15.664 12.6121C17.8681 13.7185 18.7102 12.2277 19.9029 13.0315C20.6783 13.5536 20.8826 14.6043 20.3616 15.3787C19.8144 16.1891 18.9603 16.6008 19.1077 17.93C19.1918 18.6804 19.6528 19.3259 20.2491 19.8774M4.76172 10.3535C5.98594 10.6504 7.06916 11.363 7.82643 12.3697C8.63665 13.4398 9.02255 14.7718 8.90989 16.1093C8.85309 16.7931 9.29871 17.4833 9.87212 17.8601C10.2517 18.1089 10.5691 18.4416 10.7996 18.8326C11.0301 19.2235 11.1676 19.6623 11.2015 20.1149C11.2354 20.5674 11.1647 21.0218 10.995 21.4427C10.8253 21.8636 10.5611 22.2399 10.2227 22.5424" stroke="white" stroke-width="1.6383" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="card-content">
                        <div class="card-title">Sostenibilidad</div>
                        <div class="card-description">
                            Generamos valor con gestión, <span class="bold-text">innovación y respeto ambiental</span>, empoderando de personas.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flecha derecha -->
        <button class="carousel-arrow carousel-arrow-right" @click="nextCard" @mousedown="setArrowPressed('right')" @mouseup="setArrowPressed(null)" @mouseleave="setArrowPressed(null)">
            <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 27 27" fill="none">
                <path d="M24.5425 13.5209C24.5425 7.63938 19.775 2.87193 13.8935 2.87193C8.01206 2.87193 3.24461 7.63937 3.24461 13.5209C3.24461 19.4024 8.01206 24.1698 13.8935 24.1698C19.775 24.1698 24.5425 19.4024 24.5425 13.5209ZM14.4069 17.9224C14.2535 17.7688 14.1673 17.5606 14.1673 17.3436C14.1673 17.1265 14.2535 16.9183 14.4069 16.7647L16.8316 14.34L8.97865 14.34C8.7614 14.34 8.55305 14.2537 8.39943 14.1001C8.24581 13.9465 8.1595 13.7381 8.1595 13.5209C8.1595 13.3036 8.24581 13.0953 8.39943 12.9416C8.55305 12.788 8.7614 12.7017 8.97865 12.7017L16.8316 12.7017L14.4069 10.277C14.3264 10.202 14.2618 10.1116 14.2171 10.0111C14.1723 9.91064 14.1482 9.80217 14.1463 9.69219C14.1443 9.5822 14.1646 9.47295 14.2058 9.37095C14.247 9.26895 14.3083 9.1763 14.3861 9.09851C14.4639 9.02073 14.5565 8.9594 14.6585 8.91821C14.7605 8.87701 14.8698 8.85677 14.9798 8.85872C15.0897 8.86066 15.1982 8.88473 15.2987 8.9295C15.3992 8.97427 15.4896 9.03882 15.5646 9.1193L19.3873 12.942C19.5407 13.0956 19.6269 13.3038 19.6269 13.5209C19.6269 13.7379 19.5407 13.9461 19.3873 14.0997L15.5646 17.9224C15.411 18.0758 15.2028 18.162 14.9857 18.162C14.7687 18.162 14.5605 18.0758 14.4069 17.9224Z" :fill="arrowPressed === 'right' ? '#FABD4F' : 'none'" :stroke="arrowPressed === 'right' ? 'none' : '#FABD4F'" stroke-width="1.6383"/>
            </svg>
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const currentIndex = ref(0)
const totalCards = 4
const arrowPressed = ref(null)

const nextCard = () => {
    if (currentIndex.value === totalCards - 1) {
        // Si estamos en el último card, ir al primero (loop infinito)
        currentIndex.value = 0
    } else {
        currentIndex.value++
    }
}

const previousCard = () => {
    if (currentIndex.value === 0) {
        // Si estamos en el primer card, ir al último (loop infinito)
        currentIndex.value = totalCards - 1
    } else {
        currentIndex.value--
    }
}

const setArrowPressed = (direction) => {
    arrowPressed.value = direction
}
</script>

<style scoped>
.about-carousel {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-width: 300px;
    margin: 0 auto;
}

.carousel-container {
    width: 100%;
    overflow: hidden;
    border-radius: 12px;
}

.carousel-track {
    display: flex;
    transition: transform 0.3s ease-in-out;
    width: 400%; /* 4 cards * 100% */
}

.carousel-card {
    width: 25%; /* 100% / 4 cards */
    flex-shrink: 0;
    background: #F9F9F9;
    border-radius: 12px;
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    min-height: 200px;
    box-sizing: border-box;
}

.card-icon {
    background: #007e93;
    border-radius: 28px;
    padding: 14.89px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
}

.card-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-align: center;
}

.card-title {
    color: #007e93;
    font-family: "Nexa-XBold", sans-serif;
    font-size: 18px;
    font-weight: 400;
    line-height: 96%;
    letter-spacing: 0.01em;
}

.card-description {
    color: #007e93;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 18px;
    font-weight: 400;
}

.bold-text {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 10;
}

.carousel-arrow:hover {
    transform: translateY(-50%) scale(1.05);
}

.carousel-arrow:active {
    transform: translateY(-50%) scale(0.95);
}

.carousel-arrow-left {
    left: -20px;
}

.carousel-arrow-right {
    right: -20px;
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .about-carousel {
        max-width: 280px;
    }
    
    .carousel-card {
        padding: 20px 16px;
        min-height: 180px;
    }
    
    .carousel-arrow {
        width: 36px;
        height: 36px;
    }
    
    .carousel-arrow-left {
        left: -18px;
    }
    
    .carousel-arrow-right {
        right: -18px;
    }
}
</style> 