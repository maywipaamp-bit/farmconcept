# Handoff: มาตรฐานตัวอักษรและการลดความเข้มของระบบ

## Overview
ระบบเดิม (หน้า “พื้นที่ดำเนินงาน” และหน้าอื่นในกลุ่มเดียวกัน) มีปัญหาว่าดูเข้มและหนักเกินไป
สาเหตุคือข้อความเกือบทั้งหน้าใช้ `font-weight` 600–700 สีตัวอักษรเกือบดำ ระยะบรรทัดชิด
และมีเส้นขอบล้อมทุกด้านของตาราง

ชุดไฟล์นี้กำหนดมาตรฐานฟอนต์ สี ระยะห่าง และ component พื้นฐานทั้งระบบ
เพื่อให้หน้าจอเบาลง อ่านง่ายขึ้น และดูพรีเมียมขึ้น โดย **ไม่เปลี่ยนโครงสร้างข้อมูลหรือ logic เดิม**

## About the Design Files
ไฟล์ `Typography Standard.dc.html` ในชุดนี้เป็น **design reference ที่เขียนด้วย HTML** —
เป็นเอกสารอ้างอิงภาพ ไม่ใช่โค้ด production ที่ก็อปไปใช้ตรง ๆ
งานที่ต้องทำคือ **นำมาตรฐานนี้ไปสร้างซ้ำในโค้ดเบสจริง** ตามแพตเทิร์นและไลบรารีที่ repo นั้นใช้อยู่
(React / Vue / Blade / อื่น ๆ) ถ้ายังไม่มี environment ให้เลือก framework ที่เหมาะกับโปรเจกต์แล้วทำในนั้น

ส่วน `tokens.css` และ `base.css` **นำไปใช้ได้จริง** — เป็นไฟล์ที่ออกแบบให้ import เข้า repo ได้เลย

## Fidelity
**High-fidelity (hifi)** — สี ขนาด น้ำหนัก ระยะห่าง เงา และ radius ทั้งหมดเป็นค่าสุดท้าย
ให้ทำตามค่าที่ระบุใน `tokens.css` แบบตรงตัว

## Files
| ไฟล์ | ใช้ทำอะไร |
|---|---|
| `tokens.css` | ตัวแปร CSS ทั้งหมด (ฟอนต์ สี ระยะห่าง radius เงา motion) — import ก่อนทุกไฟล์ |
| `base.css` | reset + text utilities + primitives: `.btn` `.badge` `.table` `.nav-item` `.input` `.card` `.panel` |
| `STANDARD.md` | กฎที่ต้องทำตาม + checklist — ให้ก็อปต่อท้าย `CLAUDE.md` ที่ root ของ repo |
| `CLAUDE_CODE_PROMPTS.md` | คำสั่งสำเร็จรูปสำหรับ Claude Code 6 ชุด เรียงตามลำดับการทำงาน |
| `Typography Standard.dc.html` | เอกสารอ้างอิงภาพ (type scale, before/after, หน้าจอตัวอย่าง) เปิดในเบราว์เซอร์ได้เลย |
| `support.js` | runtime ที่ไฟล์ `.dc.html` ต้องใช้ — ต้องอยู่โฟลเดอร์เดียวกัน |

## Design Tokens

### Font
- Family: `IBM Plex Sans Thai` → fallback `Anuphan` → `system-ui` → `sans-serif`
- Weights ที่ใช้: 300 / 400 / 500 / 600 — **ห้ามใช้ 700**

### Type scale
| Token | size / line-height | weight | color | ใช้กับ |
|---|---|---|---|---|
| page-title | 28 / 1.4 | 600 | #111827 | ชื่อหน้า (หน้าละหนึ่งจุด) |
| section-title | 20 / 1.45 | 600 | #111827 | หัวข้อกลุ่ม, หัว modal |
| card-title | 16 / 1.5 | 500 | #1F2937 | หัวการ์ด, ชื่อรายการที่คลิกได้ |
| body-strong | 14 / 1.6 | 500 | #1F2937 | คอลัมน์หลักของตาราง, label ฟอร์ม |
| body | 14 / 1.6 | 400 | #374151 | เนื้อหาทั่วไป, ค่าในตาราง |
| table-header | 13 / 1.5 | 500 | #6B7280 | หัวตาราง (ไม่ใช้ตัวหนา) |
| secondary | 13 / 1.6 | 400 | #6B7280 | คำอธิบายใต้ชื่อ, helper text, วันที่ |
| caption | 12 / 1.5 | 500 | ตามสถานะ | badge, tag, ตัวเลขนับ |

เล็กสุดที่อนุญาต 12px · ตัวเลขทุกจุดใช้ `font-variant-numeric: tabular-nums`

### Text colors
`#111827` strong · `#1F2937` primary · `#374151` body · `#6B7280` secondary · `#9CA3AF` muted · `#C7CDD4` faint (เลขแถว) · `#16A34A` brand — **ห้ามใช้ `#000`**

### Surfaces & borders
app `#F6F8F6` · surface `#FFFFFF` · subtle/hover `#FAFBFA` · brand-soft `#F0FDF4`
border default `#E5E7EB` · subtle `#EEF1EE` · row `#F4F6F4` · brand-soft `#BBF7D0`

### Brand & status
brand 500 `#22C55E` · 600 `#16A34A` (ปุ่มหลัก) · 700 `#15803D` (hover)
success `#15803D / #F0FDF4 / #BBF7D0` · warning `#B45309 / #FFFBEB / #FDE68A`
danger `#B91C1C / #FEF2F2 / #FECACA` · neutral `#4B5563 / #F9FAFB / #E5E7EB`

### Spacing / radius / elevation
Spacing: 4 · 8 · 12 · 16 · 24 · 32 · 48 · 64 (ใช้เฉพาะค่าเหล่านี้)
Radius: 8 / 10 / 14 / 18 / 999
card `0 1px 2px rgba(16,24,40,.04), 0 8px 24px rgba(16,24,40,.03)`
panel `0 1px 2px rgba(16,24,40,.04), 0 16px 40px rgba(16,24,40,.05)`
button `0 1px 2px rgba(22,163,74,.24)` · focus ring `0 0 0 3px rgba(22,163,74,.20)`
Motion: 150ms `cubic-bezier(0.2, 0, 0.2, 1)`

### Layout
sidebar 244px · row height 64px · content padding 32px

## Screens / Views

### 1. เอกสารมาตรฐาน (`Typography Standard.dc.html`)
**Purpose** อ้างอิงระหว่างพัฒนา ไม่ต้อง implement
เนื้อหา: สเปกฟอนต์ · กฎ 4 ข้อ · type scale 8 ระดับพร้อมตัวอย่างจริง · before/after ของตาราง ·
สีตัวอักษร 6 ระดับ · do/don't · CSS variables · ข้อแนะนำเรื่องความพรีเมียม 6 ข้อ · หน้าจอตัวอย่าง

### 2. หน้า “พื้นที่ดำเนินงาน” (หน้าจอที่ต้อง implement)
**Purpose** ดูรายการพื้นที่ดำเนินงานทั้งหมด และเพิ่มพื้นที่ใหม่

**Layout** grid สองคอลัมน์ — sidebar 244px คงที่ + content ยืดหยุ่น

**Sidebar** พื้น `#FFFFFF` · border ขวา 1px `#F1F4F1` · padding 24px 16px
- โลโก้ด้านบน + ชื่อระบบ 15px/500/`#1F2937`, gap 10px
- เมนู: gap 2px, แต่ละอันคือ `.nav-item` padding 11px 12px, radius 10px, ไอคอน 18px เส้น 1.5px `#D1D5DB`, ข้อความ 14px/400/`#4B5563`
- เมนูที่เลือก: `.nav-item--active` — พื้น `#F0FDF4`, `box-shadow: inset 2px 0 0 #16A34A`, ข้อความ 14px/500/`#15803D` (**ไม่ใช่กล่องเขียวทึบเต็มความกว้าง**)
- เมนูย่อยเยื้องซ้าย 12px

**Topbar** พื้นขาว · border ล่าง 1px `#F1F4F1` · padding 20px 32px · space-between
- breadcrumb: 13px/400/`#9CA3AF`, ตัวคั่น `/` สี `#D1D5DB`, หน้าปัจจุบัน `#4B5563`/500
- ขวา: ชื่อ 13px/500/`#1F2937` + ตำแหน่ง 12px/400/`#9CA3AF` จัดขวา + avatar 34px วงกลม

**Content** พื้น `#FCFDFC` · padding 32px · gap ระหว่างกลุ่ม 24px
- หัวหน้า: ชื่อหน้า 26–28px/600/`#111827` + บรรทัดสรุป 13px/400/`#9CA3AF` (“ทั้งหมด 3 พื้นที่ · จัดกิจกรรมแล้ว 13 ครั้ง”)
- ปุ่มมุมขวา: `ดาวน์โหลด` = `.btn` · `เพิ่มพื้นที่ดำเนินงาน` = `.btn--primary` (**เขียวได้ปุ่มเดียว**)

**ตาราง** อยู่ใน `.card` (border `#F1F4F1`, radius 14px, overflow hidden)
- คอลัมน์: `#` 40px · ชื่อพื้นที่ 2fr · ประเภทพื้นที่ 1.1fr · กลุ่มพื้นที่ 1.2fr · ผู้ประสานงาน 1.1fr · กิจกรรม 0.8fr (ขวา) · สถานะ 1.1fr
- header: padding 14px 24px, 13px/500/`#9CA3AF`, border ล่าง 1px `#F1F4F1`
- แถว: padding 18px 24px, สูง 64px, border ล่าง 1px `#F6F8F6`, **hover `#FAFBFA`**
- เลขแถว 14px/400/`#C7CDD4` tabular · ชื่อพื้นที่ 14px/500/`#1F2937` + เขตใต้ชื่อ 13px/400/`#9CA3AF` · คอลัมน์อื่น 14px/400/`#4B5563`
- สถานะ: `.badge--success` — 12px/500/`#15803D`, พื้น `#F0FDF4`, border `#BBF7D0`, pill (ไม่ใช้สีทึบ)
- footer: “แสดง 1–3 จาก 3 รายการ” 13px/`#9CA3AF` ซ้าย · pagination ขวา (หน้าปัจจุบันพื้น `#16A34A` ตัวขาว 500 radius 8px)
- **ไม่มีเส้นขอบแนวตั้ง** — เส้นคั่นแนวนอนเท่านั้น

**เนื้อหาจริงที่ใช้ในตัวอย่าง**
| # | ชื่อพื้นที่ | เขต | ประเภท | กลุ่มพื้นที่ | ผู้ประสานงาน | กิจกรรม |
|---|---|---|---|---|---|---|
| 1 | The Farm Concept | เขตบางนา กรุงเทพมหานคร | เอกชน | พื้นที่ต้นแบบ | วีระ ศรีสมบัติ | 6 |
| 2 | ชุมชนพูนทรัพย์ | เขตสายไหม กรุงเทพมหานคร | ชุมชน/หมู่บ้าน | พื้นที่ต้นแบบส่วนขยาย | อรุณี ทองสุข | 4 |
| 3 | ชุมชนตึกร้าง | เขตบางพลัด กรุงเทพมหานคร | ชุมชน/หมู่บ้าน | พื้นที่ต้นแบบส่วนขยาย | ปิยะดา รุ่งเรือง | 3 |

## Interactions & Behavior
- **Row hover** พื้นเปลี่ยนเป็น `#FAFBFA`, transition 150ms
- **Button hover** `.btn` → พื้น `#FAFBFA` ตัวอักษร `#1F2937` · `.btn--primary` → `#15803D`
- **Focus** ทุก interactive element ใช้ `:focus-visible` + `box-shadow: 0 0 0 3px rgba(22,163,74,.20)` — ไม่ใช้ outline default
- **Nav** คลิกเมนูแม่ที่มีลูก = expand/collapse · เมนูที่เลือกอยู่ใช้ `--active`
- **Loading** skeleton พื้น `#FAFBFA` radius 8px ตามรูปทรงของ cell — ไม่ใช้ spinner กลางจอ
- **Empty** ไอคอนเส้นบาง + หัวข้อ 16px/500 + คำอธิบาย 13px/muted + ปุ่ม secondary หนึ่งปุ่ม
- **Error / validation** ข้อความ 12px สี `#B91C1C` ใต้ field, border ของ input เปลี่ยนเป็น `#FECACA`
- **Responsive** ต่ำกว่า 1024px ยุบ sidebar เป็น drawer · ตารางเลื่อนแนวนอนได้ ไม่ลดขนาดฟอนต์

## State Management
หน้าจอนี้เป็น list view มาตรฐาน: `rows`, `loading`, `error`, `page`, `perPage`, `total`, `search`, `filter`, `sort`, `openRowMenuId`
ไม่มี state พิเศษที่มาจากงานออกแบบนี้ — งานนี้เป็นชั้นการนำเสนอ

## Assets
- ฟอนต์: IBM Plex Sans Thai (Google Fonts, SIL Open Font License) — `tokens.css` มี `@import` ให้แล้ว; ถ้า repo self-host ให้เปลี่ยนเป็น `@font-face` ของ repo
- ไอคอน: ให้ใช้ชุดไอคอนเส้นที่ repo มีอยู่แล้ว (เช่น Lucide / Heroicons outline) เส้น 1.5px — ในเอกสารอ้างอิงเป็นรูปทรงแทนที่ไว้เท่านั้น
- โลโก้: ใช้ไฟล์โลโก้จริงของ The Farm Concept จาก repo

## เริ่มงานอย่างไร
เปิด `CLAUDE_CODE_PROMPTS.md` แล้วทำตามลำดับ: ติดตั้ง tokens → สแกนหาจุดที่ผิดกฎ → แก้ทีละหน้า →
สร้าง component กลาง → ใส่ lint กันถอยหลัง → เก็บ empty/loading state
