async function submitApplication(payload) {
  const fd = new FormData();

  // top-level
  fd.append("api_token", payload.api_token);
  fd.append("jobId", payload.jobId);

  // Part 1
  fd.append("full_name", payload.personal.full_name);
  fd.append("id_number", payload.personal.id_number);
  fd.append("email", payload.personal.email);
  fd.append("phone", payload.personal.phone);
  fd.append("gender", payload.personal.gender);
  fd.append("dob", payload.personal.dob); // yyyy-mm-dd
  fd.append("nationality", payload.personal.nationality);
  fd.append("plwd", payload.personal.plwd ? "1" : "0");

  // Part 2: Academics
  payload.academics.forEach((a, i) => {
    fd.append(`academics[${i}][qualification_level]`, a.qualification_level);
    fd.append(`academics[${i}][institution_name]`, a.institution_name);
    fd.append(`academics[${i}][institution_country]`, a.institution_country || "");
    fd.append(`academics[${i}][qualification_name]`, a.qualification_name);
    fd.append(`academics[${i}][certificate_number]`, a.certificate_number || "");
    fd.append(`academics[${i}][year_completed]`, a.year_completed ? String(a.year_completed) : "");

    // multiple files for this academic index
    (a.attachments || []).forEach((file) => {
      fd.append(`academics_attachments[${i}][]`, file);
    });
  });

  // Part 3: Work experiences
  payload.work_experiences.forEach((w, i) => {
    fd.append(`work_experiences[${i}][employer_name]`, w.employer_name);
    fd.append(`work_experiences[${i}][employer_contact]`, w.employer_contact || "");
    fd.append(`work_experiences[${i}][location]`, w.location || "");
    fd.append(`work_experiences[${i}][job_title]`, w.job_title);
    fd.append(`work_experiences[${i}][start_date]`, w.start_date); // yyyy-mm-dd
    fd.append(`work_experiences[${i}][end_date]`, w.end_date || "");
    fd.append(`work_experiences[${i}][is_current]`, w.is_current ? "1" : "0");
    fd.append(`work_experiences[${i}][achievements]`, w.achievements || "");
  });

  // Part 4: memberships
  (payload.memberships || []).forEach((m, i) => {
    fd.append(`memberships[${i}][organization_name]`, m.organization_name);
    fd.append(`memberships[${i}][membership_number]`, m.membership_number);
    fd.append(`memberships[${i}][membership_type]`, m.membership_type || "");
    fd.append(`memberships[${i}][year_joined]`, m.year_joined ? String(m.year_joined) : "");

    // one cert per membership index
    if (m.certificate_file) {
      fd.append(`membership_certificate[${i}]`, m.certificate_file);
    }
  });

  // Part 5 docs
  fd.append("cv", payload.documents.cv);
  fd.append("national_id", payload.documents.national_id);

  (payload.documents.other_documents || []).forEach((file) => {
    fd.append("other_documents[]", file);
  });

  const res = await fetch("https://your-domain.com/api/external/apply", {
    method: "POST",
    body: fd,
  });

  const data = await res.json();
  if (!res.ok || data?.success === false) {
    throw new Error(data?.message || data?.error || "Submission failed");
  }
  return data;
}




//using axios
import axios from "axios";

async function submitApplicationAxios(payload) {
  const fd = new FormData();

  // same fd.append(...) as above

  const { data } = await axios.post(
    "https://your-domain.com/api/external/apply",
    fd,
    { headers: { "Content-Type": "multipart/form-data" } }
  );

  return data;
}