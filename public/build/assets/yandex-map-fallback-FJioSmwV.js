class a{constructor(t,e={}){this.containerId=t,this.options=e,this.map=null,this.attempts=0,this.maxAttempts=10,this.init()}init(){if(this.attempts>=this.maxAttempts){console.error("❌ Превышено максимальное количество попыток инициализации карты"),this.showFallback();return}if(this.attempts++,console.log(`🔄 Попытка инициализации #${this.attempts}`),document.readyState!=="complete"){setTimeout(()=>this.init(),100);return}const t=document.getElementById(this.containerId);if(!t){console.error("❌ Контейнер карты не найден"),setTimeout(()=>this.init(),200);return}if(this.forceContainerStyles(t),typeof ymaps=="undefined"){console.log("⏳ Ожидаем загрузку Яндекс Карт..."),setTimeout(()=>this.init(),200);return}this.initializeMap()}forceContainerStyles(t){t.style.cssText=`
            width: 100% !important;
            height: 400px !important;
            min-height: 400px !important;
            position: relative !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1 !important;
            background: #e9ecef !important;
        `;let e=t.parentElement;for(;e&&e!==document.body;)e.style.overflow="visible",e.style.position="relative",e=e.parentElement}initializeMap(){try{console.log("🎯 Создаем карту..."),ymaps.ready(()=>{if(this.map)return;const e=document.getElementById(this.containerId),o=document.createElement("div");if(o.id=this.containerId,o.style.cssText=e.style.cssText,e.parentNode.replaceChild(o,e),this.map=new ymaps.Map(this.containerId,{center:this.options.center||[55.863631,37.652714],zoom:this.options.zoom||16,controls:this.options.controls||["zoomControl","fullscreenControl","typeSelector","searchControl"]},{suppressMapOpenBlock:!0,yandexMapDisablePoiInteractivity:!1}),this.options.placemark){const i=new ymaps.Placemark(this.options.placemark.center||this.options.center||[55.863631,37.652714],this.options.placemark.properties||{hintContent:"Федеральная Арендная Платформа",balloonContentHeader:"Федеральная Арендная Платформа",balloonContentBody:this.options.placemark.content||`
                                <div style="max-width: 250px;">
                                    <p style="margin: 8px 0; font-size: 14px;">
                                        <strong>Адрес:</strong><br>
                                        ${this.options.address||"ул. Искры, 31, Москва"}
                                    </p>
                                    <p style="margin: 8px 0; font-size: 14px;">
                                        <strong>Телефон:</strong><br>
                                        ${this.options.phone||"+7 (929) 533-32-06"}
                                    </p>
                                </div>
                            `},this.options.placemark.options||{preset:"islands#blueBusinessIcon",iconColor:"#0056b3"});this.map.geoObjects.add(i),setTimeout(()=>{try{i.balloon.open()}catch(s){console.warn("Не удалось открыть балун:",s)}},2e3)}this.map.events.add("load",()=>{console.log("✅ Карта успешно загружена!"),this.onMapLoaded()}),this.map.events.add("error",i=>{console.error("❌ Ошибка карты:",i),this.showFallback()}),this.forceMapRedraw()})}catch(t){console.error("💥 Критическая ошибка:",t),this.showFallback()}}onMapLoaded(){const t=document.getElementById("map-loader");t&&(t.style.display="none"),this.forceMapRedraw(),setTimeout(()=>this.forceMapRedraw(),100),setTimeout(()=>this.forceMapRedraw(),500),setTimeout(()=>this.forceMapRedraw(),1e3)}forceMapRedraw(){if(this.map)try{this.map.container&&this.map.container.fitToViewport&&this.map.container.fitToViewport(),this.map.container&&this.map.container.redraw&&this.map.container.redraw();const t=document.getElementById(this.containerId);t&&(t.style.display="none",t.offsetHeight,t.style.display="block"),console.log("🔄 Размеры карты обновлены")}catch(t){console.warn("Не удалось обновить размеры карты:",t)}}showFallback(){console.log("🔄 Активируем fallback...");const t=document.getElementById(this.containerId),e=document.getElementById("map-loader");if(!t)return;const o=document.createElement("div");o.innerHTML=`
            <div style="width: 100%; height: 400px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                <div class="text-center">
                    <div style="font-size: 48px; color: #dc3545; margin-bottom: 16px;">🗺️</div>
                    <h5>Интерактивная карта недоступна</h5>
                    <p class="text-muted">Используется статическая карта</p>
                    <img src="https://static-maps.yandex.ru/1.x/?ll=37.652714,55.863631&z=16&size=650,400&l=map&pt=37.652714,55.863631,pm2dbl"
                         alt="Федеральная Арендная Платформа"
                         style="width: 100%; height: 300px; object-fit: cover; border-radius: 8px;">
                    <p class="mt-2">
                        <strong>Адрес:</strong> ${this.options.address||"ул. Искры, 31, Москва"}<br>
                        <strong>Телефон:</strong> ${this.options.phone||"+7 (929) 533-32-06"}
                    </p>
                </div>
            </div>
        `,t.parentNode.replaceChild(o,t),e&&(e.style.display="none")}destroy(){this.map&&(this.map.destroy(),this.map=null)}}window.initYandexMap=function(n,t={}){return new a(n,t)};
