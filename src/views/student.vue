<template>
  <main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <h1 class="h2 mb-0">รายชื่อนักศึกษา</h1>
      <button type="button" class="btn btn-primary" @click="openAddModal">
        เพิ่มนักศึกษา
      </button>
    </div>

    <div v-if="feedback" class="alert" :class="`alert-${feedbackType}`" role="alert">
      {{ feedback }}
    </div>
    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
    <div v-if="loading" class="text-center py-4">กำลังโหลดข้อมูล...</div>

    <div v-else-if="!error && students.length === 0" class="alert alert-info">
      ยังไม่มีข้อมูลนักศึกษา
    </div>

    <div v-else-if="students.length" class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th scope="col">รหัสนักศึกษา</th>
            <th scope="col">ชื่อ</th>
            <th scope="col">นามสกุล</th>
            <th scope="col">เบอร์โทร</th>
            <th scope="col">อีเมล</th>
            <th scope="col">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="student in students" :key="student.student_id">
            <td>{{ student.student_id }}</td>
            <td>{{ student.first_name }}</td>
            <td>{{ student.last_name }}</td>
            <td>{{ student.phone || "-" }}</td>
            <td>{{ student.email || "-" }}</td>
            <td class="text-nowrap">
              <button
                type="button"
                class="btn btn-warning btn-sm me-2"
                @click="openEditModal(student)"
              >
                แก้ไข
              </button>
              <button
                type="button"
                class="btn btn-danger btn-sm"
                :disabled="deletingId === student.student_id"
                @click="deleteStudent(student.student_id)"
              >
                {{ deletingId === student.student_id ? "กำลังลบ..." : "ลบ" }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      id="studentModal"
      ref="studentModalElement"
      class="modal fade"
      tabindex="-1"
      aria-labelledby="studentModalTitle"
      aria-hidden="true"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <form @submit.prevent="saveStudent">
            <div class="modal-header">
              <h2 id="studentModalTitle" class="modal-title fs-5">
                {{ isEditMode ? "แก้ไขข้อมูลนักศึกษา" : "เพิ่มนักศึกษา" }}
              </h2>
              <button
                type="button"
                class="btn-close"
                aria-label="ปิด"
                @click="closeModal"
              ></button>
            </div>
            <div class="modal-body">
              <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
              <div class="mb-3">
                <label for="studentFirstName" class="form-label">ชื่อ</label>
                <input
                  id="studentFirstName"
                  v-model.trim="form.first_name"
                  type="text"
                  class="form-control"
                  autocomplete="given-name"
                  maxlength="50"
                  required
                >
              </div>
              <div class="mb-3">
                <label for="studentLastName" class="form-label">นามสกุล</label>
                <input
                  id="studentLastName"
                  v-model.trim="form.last_name"
                  type="text"
                  class="form-control"
                  autocomplete="family-name"
                  maxlength="50"
                  required
                >
              </div>
              <div class="mb-3">
                <label for="studentPhone" class="form-label">เบอร์โทร</label>
                <input
                  id="studentPhone"
                  v-model.trim="form.phone"
                  type="tel"
                  class="form-control"
                  autocomplete="tel"
                  maxlength="15"
                >
              </div>
              <div class="mb-3">
                <label for="studentEmail" class="form-label">อีเมล</label>
                <input
                  id="studentEmail"
                  v-model.trim="form.email"
                  type="email"
                  class="form-control"
                  autocomplete="email"
                  maxlength="100"
                >
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeModal">
                ยกเลิก
              </button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? "กำลังบันทึก..." : (isEditMode ? "บันทึกการแก้ไข" : "เพิ่มนักศึกษา") }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
</template>

<script>
import { Modal } from "bootstrap";
import { onBeforeUnmount, onMounted, ref } from "vue";

const studentsApi = "http://localhost/week7-main/mid-68708100/php_api/student.php";
const emptyForm = () => ({
  student_id: null,
  first_name: "",
  last_name: "",
  phone: "",
  email: ""
});

export default {
  name: "StudentManagement",
  setup() {
    const students = ref([]);
    const loading = ref(true);
    const saving = ref(false);
    const deletingId = ref(null);
    const error = ref("");
    const feedback = ref("");
    const feedbackType = ref("success");
    const form = ref(emptyForm());
    const isEditMode = ref(false);
    const studentModalElement = ref(null);
    let studentModal;

    const readResult = async (response) => {
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล");
      }
      return result;
    };

    const fetchStudents = async () => {
      loading.value = true;
      error.value = "";
      try {
        const response = await fetch(studentsApi);
        const result = await readResult(response);
        students.value = result.data;
      } catch (err) {
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };

    const openAddModal = () => {
      error.value = "";
      feedback.value = "";
      isEditMode.value = false;
      form.value = emptyForm();
      studentModal.show();
    };

    const openEditModal = (student) => {
      error.value = "";
      feedback.value = "";
      isEditMode.value = true;
      form.value = { ...student };
      studentModal.show();
    };

    const closeModal = () => {
      studentModal.hide();
    };

    const saveStudent = async () => {
      saving.value = true;
      error.value = "";
      try {
        const response = await fetch(studentsApi, {
          method: isEditMode.value ? "PUT" : "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(form.value)
        });
        const result = await readResult(response);
        studentModal.hide();
        feedbackType.value = "success";
        feedback.value = result.message;
        await fetchStudents();
      } catch (err) {
        error.value = err.message;
      } finally {
        saving.value = false;
      }
    };

    const deleteStudent = async (id) => {
      if (!confirm("คุณต้องการลบข้อมูลนักศึกษานี้ใช่หรือไม่?")) return;

      deletingId.value = id;
      error.value = "";
      feedback.value = "";
      try {
        const response = await fetch(studentsApi, {
          method: "DELETE",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ student_id: id })
        });
        const result = await readResult(response);
        feedbackType.value = "success";
        feedback.value = result.message;
        await fetchStudents();
      } catch (err) {
        error.value = err.message;
      } finally {
        deletingId.value = null;
      }
    };

    onMounted(() => {
      studentModal = new Modal(studentModalElement.value);
      fetchStudents();
    });

    onBeforeUnmount(() => {
      studentModal?.dispose();
    });

    return {
      students,
      loading,
      saving,
      deletingId,
      error,
      feedback,
      feedbackType,
      form,
      isEditMode,
      studentModalElement,
      openAddModal,
      openEditModal,
      closeModal,
      saveStudent,
      deleteStudent
    };
  }
};
</script>
