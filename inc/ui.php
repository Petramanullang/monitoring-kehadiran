<?php

function ui_page_start($title, $subtitle = '') {
  $sub = $subtitle ? "<div class='text-sm text-black/70'>$subtitle</div>" : "";
  return "
  <div class='mb-6'>
    <div class='inline-block bg-yellow-300 px-3 py-1 border-2 border-black shadow-[6px_6px_0_0_#000]'>
      <div class='text-lg font-black tracking-tight'>$title</div>
    </div>
    <div class='mt-2'>$sub</div>
  </div>
  ";
}

function ui_card_start($title = '', $right = '') {
  $head = $title !== '' ? "
    <div class='flex items-start justify-between gap-3 pb-3 mb-4 border-b-2 border-black'>
      <div class='font-extrabold text-black'>$title</div>
      <div>$right</div>
    </div>
  " : "";
  return "
  <section class='bg-white border-2 border-black shadow-[8px_8px_0_0_#000] p-5'>
    $head
  ";
}
function ui_card_end(){ return "</section>"; }

function ui_input_class() {
  return "w-full bg-white border-2 border-black px-3 py-2 text-black outline-none
          focus:ring-0 focus:border-black shadow-[4px_4px_0_0_#000]";
}
function ui_select_class(){ return ui_input_class(); }

function ui_btn_class($variant='solid') {
  return match($variant) {
    'solid' => "inline-flex items-center justify-center gap-2 bg-black text-white border-2 border-black
                px-4 py-2 font-extrabold shadow-[6px_6px_0_0_#000] hover:translate-x-[1px] hover:translate-y-[1px]
                hover:shadow-[5px_5px_0_0_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-[4px_4px_0_0_#000] transition",
    'alt'   => "inline-flex items-center justify-center gap-2 bg-cyan-300 text-black border-2 border-black
                px-4 py-2 font-extrabold shadow-[6px_6px_0_0_#000] hover:translate-x-[1px] hover:translate-y-[1px]
                hover:shadow-[5px_5px_0_0_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-[4px_4px_0_0_#000] transition",
    'danger'=> "inline-flex items-center justify-center gap-2 bg-rose-400 text-black border-2 border-black
                px-4 py-2 font-extrabold shadow-[6px_6px_0_0_#000] hover:translate-x-[1px] hover:translate-y-[1px]
                hover:shadow-[5px_5px_0_0_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-[4px_4px_0_0_#000] transition",
    'ghost' => "inline-flex items-center justify-center gap-2 bg-white text-black border-2 border-black
                px-4 py-2 font-extrabold shadow-[6px_6px_0_0_#000] hover:bg-yellow-200 transition",
    default => "inline-flex items-center justify-center gap-2 bg-black text-white border-2 border-black
                px-4 py-2 font-extrabold shadow-[6px_6px_0_0_#000] transition",
  };
}

function ui_badge($text, $tone='neutral') {
  $cls = match($tone){
    'ok' => "bg-emerald-200",
    'warn' => "bg-yellow-200",
    'bad' => "bg-rose-200",
    'info' => "bg-cyan-200",
    default => "bg-white",
  };
  return "<span class='$cls border-2 border-black px-2 py-0.5 text-xs font-black shadow-[3px_3px_0_0_#000]'>$text</span>";
}

function ui_table_start($cols) {
  $ths = "";
  foreach($cols as $c){
    $ths .= "<th class='text-left px-3 py-3 border-b-2 border-black font-black text-black'>$c</th>";
  }
  return "
  <div class='overflow-x-auto border-2 border-black shadow-[8px_8px_0_0_#000] bg-white'>
    <table class='min-w-full text-sm'>
      <thead class='bg-yellow-200'>$ths</thead>
      <tbody class='divide-y-2 divide-black'>
  ";
}
function ui_table_end() {
  return "
      </tbody>
    </table>
  </div>
  ";
}

function ui_row_start(){ return "<tr class='hover:bg-cyan-50 transition'>"; }
function ui_row_end(){ return "</tr>"; }

function ui_td($html, $nowrap=false){
  $nw = $nowrap ? "whitespace-nowrap" : "";
  return "<td class='px-3 py-3 text-black $nw'>$html</td>";
}

function ui_empty($msg, $hint='') {
  $h = $hint ? "<div class='text-sm text-black/70 mt-1'>$hint</div>" : "";
  return "
  <div class='bg-white border-2 border-black shadow-[8px_8px_0_0_#000] p-6'>
    <div class='font-extrabold text-black'>$msg</div>
    $h
  </div>
  ";
}
