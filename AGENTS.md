# TheFarmConcept — Codex Project Instructions

## Project Context
โครงการนี้คือระบบติดตามและประเมินผลการเปลี่ยนแปลงสุขภาพ ภายใต้ TheFarmConcept
ประกอบด้วย Public Website, ระบบลงทะเบียนกิจกรรม, ระบบประเมิน, ระบบติดตามผล และ Backoffice

## Core Rules
- อ่านไฟล์นี้และเอกสารในโฟลเดอร์ `docs/` ก่อนเริ่มงานทุกครั้ง
- ใช้โครงสร้างเดิมของโปรเจกต์ก่อนสร้างโครงสร้างใหม่
- Reuse Layout, Component, Utility, CSS และ JavaScript เดิมให้มากที่สุด
- ห้ามสร้าง Component ใหม่ หาก Component เดิมรองรับได้
- ห้ามเขียนโค้ดซ้ำ
- ห้ามเปลี่ยน UI เดิมนอกเหนือจากขอบเขตที่สั่ง
- ห้ามสร้าง แก้ไข หรือลบฐานข้อมูลโดยไม่ได้รับอนุญาต
- ก่อนแก้ไฟล์สำคัญ ต้องตรวจสอบผลกระทบต่อหน้าจออื่น
- โค้ดต้องอ่านง่าย กระชับ บำรุงรักษาง่าย และรองรับการขยายระบบ

## UI/UX Standard
- Theme: White + Green
- Primary Color: `#81C060`
- Primary Hover: `#6FB24E`
- Dark Green: `#2F6D45`
- Background: `#F8FAF8`
- Surface: `#FFFFFF`
- Border: `#E5E7EB`
- Text Primary: `#1F2937`
- Text Secondary: `#6B7280`
- Font: Noto Sans Thai
- Style: Modern, Minimal, Clean, Professional, Enterprise
- Responsive: Mobile, Tablet, Desktop
- ใช้ White Space อย่างเหมาะสม
- หลีกเลี่ยง Gradient, Glassmorphism และ Animation ที่ไม่จำเป็น

## Reusable UI
ให้ตรวจสอบและ Reuse Component ต่อไปนี้ก่อนสร้างใหม่
- Button
- Input
- Select
- Textarea
- Checkbox
- Radio
- Switch
- Search
- Filter
- Card
- Table
- Pagination
- Modal
- Drawer
- Toast
- Badge
- Tabs
- Timeline
- Empty State
- Loading
- Skeleton
- Upload
- Date Picker

## Screen Templates
ให้เลือกใช้ Template ที่เหมาะสมก่อนเริ่มพัฒนา
- CRUD List
- Form Create/Edit
- Detail
- Dashboard
- Report
- Timeline
- Public Event
- Registration
- Evaluation

## Frontend Rules
- ใช้ Semantic HTML
- ใช้ CSS Variable หรือ Design Token กลาง
- ห้าม Hardcode สีหรือระยะซ้ำหลายจุด
- ทุกปุ่มที่คลิกได้ต้องมี cursor pointer
- ทุก Action ต้องตอบสนองรวดเร็ว
- ป้องกันการกดซ้ำระหว่าง Loading
- มี Validation, Error, Empty State และ Loading State
- รองรับ Keyboard Navigation และ Accessibility ขั้นพื้นฐาน

## Authentication Rules
- ตรวจสอบ Session ก่อน Render หน้าจอ
- ห้ามแสดงหน้า Login ชั่วครู่ก่อน Redirect เข้าระบบ
- ใช้ Loading/Auth Guard ระหว่างตรวจสอบสถานะ
- Refresh หน้าแล้วต้องไม่เกิด Flicker หรือ Redirect ซ้ำ

## Backend Rules
- แยก Logic ออกจาก View
- ใช้ Validation กลาง
- ใช้ Service/Action เมื่อ Logic ซับซ้อน
- ใช้ Transaction เมื่อมีหลายขั้นตอนที่ต้องสำเร็จพร้อมกัน
- ป้องกัน N+1 Query
- ตรวจสอบ Authorization ทุก Action
- ไม่เปิดเผยข้อมูลสำคัญใน Error Message

## Database Rules
- ห้ามสร้าง Migration, Table, Column, Index หรือ Seed โดยไม่ได้รับอนุญาต
- หากจำเป็นต้องเปลี่ยนฐานข้อมูล ให้เสนอรายการเปลี่ยนแปลงก่อน
- ระบุผลกระทบ ข้อมูลเดิม และวิธี Rollback
- ใช้ชื่อตารางและคอลัมน์ภาษาอังกฤษแบบสม่ำเสมอ

## Before Coding
1. วิเคราะห์ Module ที่เกี่ยวข้อง
2. ตรวจสอบไฟล์และ Component เดิม
3. ระบุไฟล์ที่จะเปลี่ยน
4. เลือก Template ที่ใช้
5. วางแผน Reuse
6. ตรวจสอบผลกระทบ

## After Coding
- ตรวจ Syntax และ Error
- ทดสอบ Desktop, Tablet, Mobile
- ทดสอบ Empty, Loading, Error และ Success State
- ตรวจปุ่ม Action และ Modal
- ตรวจ Refresh และ Authentication
- สรุปไฟล์ที่เปลี่ยนและผลลัพธ์

## Expected Output From Codex
ทุกครั้งที่ทำงาน ให้สรุปดังนี้
1. สิ่งที่ตรวจพบ
2. แนวทางที่เลือก
3. ไฟล์ที่แก้ไข
4. สิ่งที่พัฒนา
5. วิธีทดสอบ
6. ข้อควรระวังหรือสิ่งที่ยังไม่ได้ทำ
