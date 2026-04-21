<?php
/**
 * includes/attach_widget.php
 * Renders a self-contained file attachment widget for any entity.
 * Call: renderAttachWidget('task', $task_id)
 *       renderAttachWidget('project', $proj_id)
 *       renderAttachWidget('contact', $contact_id)
 *       renderAttachWidget('lead', $lead_id)
 *
 * Requires: config.php already loaded (for currentUser / isManager)
 * CSS/JS is output once via a static flag.
 */

function renderAttachWidget(string $entity, int $entity_id): void {
    static $css_done = false;
    if (!$css_done) {
        $css_done = true;
        echo <<<'CSS'
<style>
.aw-wrap{margin-top:18px}
.aw-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.aw-title{font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.05em}
.aw-list{display:flex;flex-direction:column;gap:5px;min-height:0}
.aw-item{display:flex;align-items:center;gap:8px;padding:7px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);transition:border-color .12s}
.aw-item:hover{border-color:var(--border2)}
.aw-icon{font-size:16px;flex-shrink:0;width:22px;text-align:center}
.aw-info{flex:1;min-width:0}
.aw-name{font-size:12.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.aw-meta{font-size:11px;color:var(--text3);margin-top:1px}
.aw-dl{font-size:11.5px;color:var(--orange);text-decoration:none;flex-shrink:0;font-weight:600}
.aw-dl:hover{text-decoration:underline}
.aw-del{background:none;border:none;cursor:pointer;color:var(--text3);font-size:13px;padding:2px 5px;border-radius:3px;flex-shrink:0;line-height:1}
.aw-del:hover{color:var(--red);background:rgba(239,68,68,.1)}
.aw-empty{font-size:12px;color:var(--text3);padding:8px 0;text-align:center}
.aw-drop{border:2px dashed var(--border);border-radius:var(--radius-sm);padding:14px;text-align:center;font-size:12.5px;color:var(--text3);cursor:pointer;transition:border-color .15s,background .15s;margin-top:8px;position:relative}
.aw-drop:hover,.aw-drop.drag-over{border-color:var(--orange);background:var(--orange-bg);color:var(--orange)}
.aw-drop input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.aw-uploading{font-size:12px;color:var(--orange);padding:6px 0;text-align:center;display:none}
.aw-progress{height:3px;background:var(--bg4);border-radius:2px;margin-top:6px;overflow:hidden;display:none}
.aw-progress-fill{height:100%;background:var(--orange);border-radius:2px;transition:width .2s}
</style>
CSS;
        echo <<<'JS'
<script>
function awFmtSize(b){if(b<1024)return b+'B';if(b<1048576)return(b/1024).toFixed(1)+'KB';return(b/1048576).toFixed(1)+'MB';}
function awExtIcon(ext){var m={'pdf':'📄','doc':'📝','docx':'📝','xls':'📊','xlsx':'📊','ppt':'📑','pptx':'📑','zip':'🗜','rar':'🗜','txt':'📃','jpg':'🖼','jpeg':'🖼','png':'🖼','gif':'🖼','mp4':'🎬','mp3':'🎵'};return m[ext]||'📎';}
function awCanDelete(uploaderId){return true;} // server enforces; UI shows for all

function awLoad(entity, eid) {
    var list = document.getElementById('aw-list-'+entity+'-'+eid);
    if (!list) return;
    fetch('attach_list.php?entity='+entity+'&id='+eid)
        .then(function(r){return r.json();})
        .then(function(items){
            if (!items.length) {
                list.innerHTML = '<div class="aw-empty">No attachments yet</div>';
                return;
            }
            list.innerHTML = items.map(function(f){
                return '<div class="aw-item" id="aw-file-'+f.id+'">'
                    +'<div class="aw-icon">'+awExtIcon(f.ext)+'</div>'
                    +'<div class="aw-info">'
                        +'<div class="aw-name">'+f.name+'</div>'
                        +'<div class="aw-meta">'+awFmtSize(f.size)+' · '+f.by+' · '+f.date+'</div>'
                    +'</div>'
                    +'<a href="'+f.url+'" class="aw-dl" title="Download">↓</a>'
                    +'<button class="aw-del" onclick="awDelete('+f.id+',\''+entity+'\','+eid+')" title="Remove">✕</button>'
                +'</div>';
            }).join('');
        });
}

function awDelete(id, entity, eid) {
    if (!confirm('Remove this attachment?')) return;
    var el = document.getElementById('aw-file-'+id);
    if (el) { el.style.opacity='0.4'; el.style.pointerEvents='none'; }
    var fd = new FormData();
    fd.append('action','delete'); fd.append('id',id);
    fetch('attach_upload.php', {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                if (el) el.remove();
                var list = document.getElementById('aw-list-'+entity+'-'+eid);
                if (list && !list.children.length) list.innerHTML='<div class="aw-empty">No attachments yet</div>';
                if(typeof toast==='function') toast('Attachment removed','success');
            } else {
                if (el) { el.style.opacity='1'; el.style.pointerEvents=''; }
                if(typeof toast==='function') toast(d.error||'Delete failed','error');
            }
        });
}

function awUpload(input, entity, eid) {
    var f = input.files[0];
    if (!f) return;
    var drop  = document.getElementById('aw-drop-'+entity+'-'+eid);
    var prog  = document.getElementById('aw-prog-'+entity+'-'+eid);
    var progf = document.getElementById('aw-progf-'+entity+'-'+eid);
    var upl   = document.getElementById('aw-upl-'+entity+'-'+eid);

    if (upl)  { upl.style.display='block'; upl.textContent='Uploading '+f.name+'…'; }
    if (prog) prog.style.display='block';
    if (drop) drop.style.pointerEvents='none';

    var fd = new FormData();
    fd.append('action','upload');
    fd.append('entity', entity);
    fd.append('entity_id', eid);
    fd.append('file', f);

    var xhr = new XMLHttpRequest();
    xhr.upload.onprogress = function(e){
        if (e.lengthComputable && progf) progf.style.width=Math.round(e.loaded/e.total*100)+'%';
    };
    xhr.onload = function(){
        if (upl)  { upl.style.display='none'; }
        if (prog) prog.style.display='none';
        if (drop) drop.style.pointerEvents='';
        input.value = '';
        try {
            var d = JSON.parse(xhr.responseText);
            if (d.ok) {
                awLoad(entity, eid);
                if(typeof toast==='function') toast('File attached','success');
            } else {
                if(typeof toast==='function') toast(d.error||'Upload failed','error');
            }
        } catch(e) {
            if(typeof toast==='function') toast('Upload failed','error');
        }
    };
    xhr.onerror = function(){
        if (upl) upl.style.display='none';
        if (prog) prog.style.display='none';
        if (drop) drop.style.pointerEvents='';
        if(typeof toast==='function') toast('Upload error','error');
    };
    xhr.open('POST','attach_upload.php');
    xhr.send(fd);
}

function awDragOver(el){el.classList.add('drag-over');}
function awDragLeave(el){el.classList.remove('drag-over');}
function awDrop(e,input,entity,eid){
    e.preventDefault();
    var el=document.getElementById('aw-drop-'+entity+'-'+eid);
    if(el)el.classList.remove('drag-over');
    if(e.dataTransfer.files.length){
        input.files=e.dataTransfer.files;
        awUpload(input,entity,eid);
    }
}
</script>
JS;
    }

    // Widget HTML
    $wid = $entity.'-'.$entity_id;
    echo '<div class="aw-wrap" id="aw-wrap-'.$wid.'">';
    echo '<div class="aw-head">';
    echo '<span class="aw-title">📎 Attachments</span>';
    echo '</div>';
    echo '<div class="aw-list" id="aw-list-'.$wid.'"><div class="aw-empty">Loading…</div></div>';
    echo '<div class="aw-drop" id="aw-drop-'.$wid.'"
        ondragover="event.preventDefault();awDragOver(this)"
        ondragleave="awDragLeave(this)"
        ondrop="awDrop(event,document.getElementById(\'aw-input-'.$wid.'\'),\''.$entity.'\','.$entity_id.')">';
    echo '📎 Click to attach or drag &amp; drop a file';
    echo '<input type="file" id="aw-input-'.$wid.'"
        onchange="awUpload(this,\''.$entity.'\','.$entity_id.')"
        accept=".'.implode(',.',ALLOWED_DOC_TYPES).'">';
    echo '</div>';
    echo '<div class="aw-uploading" id="aw-upl-'.$wid.'"></div>';
    echo '<div class="aw-progress" id="aw-prog-'.$wid.'"><div class="aw-progress-fill" id="aw-progf-'.$wid.'" style="width:0%"></div></div>';
    echo '</div>';
    echo '<script>awLoad("'.$entity.'",'.$entity_id.');</script>';
}