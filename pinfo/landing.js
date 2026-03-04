const competitions = [
{
title:"SONIC LINGUISTIC",
image:"Assets/linguistik.jpeg",
location:"Online",
date:"5 Feb - 10 Apr 2026",
category:"Programming",
description:"Kompetisi bahasa dan literasi untuk pelajar SMP & SMA se-Indonesia."
},
{
title:"UI UX Design Contest",
image:"Assets/ux.jpeg",
location:"Jakarta",
date:"10 Mar - 15 Apr 2026",
category:"Design",
description:"Kompetisi desain antarmuka untuk mahasiswa dan fresh graduate."
}
];

function renderCards(){
const grid = document.getElementById("cardGrid");
const searchValue = document.getElementById("searchInput").value.toLowerCase();
const categoryValue = document.getElementById("categoryFilter").value;

let html = "";

competitions.forEach((comp,index)=>{
if(
comp.title.toLowerCase().includes(searchValue) &&
(categoryValue==="All"||comp.category===categoryValue)
){
html+=`
<div class="card">
<img src="${comp.image}">
<div class="card-body">
<h3>${comp.title}</h3>
<div class="card-meta">
<span>📍 ${comp.location}</span>
<span>📅 ${comp.date}</span>
</div>
<button class="btn-detail" onclick="openDetail(${index})">More Detail</button>
</div>
</div>`;
}
});

grid.innerHTML = html || "<p>Tidak ada lomba ditemukan</p>";
}

function openDetail(index){
const comp = competitions[index];
document.getElementById("modalTitle").innerText = comp.title;
document.getElementById("modalImage").src = comp.image;
document.getElementById("modalLocation").innerText = "📍 "+comp.location;
document.getElementById("modalDate").innerText = "📅 "+comp.date;
document.getElementById("modalDescription").innerText = comp.description;
document.getElementById("detailModal").style.display="flex";
}

function closeDetail(){
document.getElementById("detailModal").style.display="none";
}

window.onclick = function(event){
if(event.target==document.getElementById("detailModal")){
closeDetail();
}
}

renderCards();
