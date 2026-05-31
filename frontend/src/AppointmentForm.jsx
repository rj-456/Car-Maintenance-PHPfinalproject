import React, { useState } from 'react';
import './AppointmentForm.css';

const BrandLogo = () => (
    <svg className="brand-logo-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="rgba(255,90,31,0.5)" strokeWidth="1" />
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" strokeWidth="1.5" />
    </svg>
);

const AppointmentForm = () => {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        service_date: '',
        vehicle_model: '',
        custom_vehicle_model: '',
        service_type: '',
        custom_service_type: '',
        media_url: '',
        media_file: '',
        media_file_name: '',
        notes: ''
    });

    const [status, setStatus] = useState({ type: '', message: '', errors: {} });
    const [submittedData, setSubmittedData] = useState(null);
    const [isConfirming, setIsConfirming] = useState(false);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onloadend = () => {
            setFormData(prev => ({
                ...prev,
                media_file: reader.result, // base64 string
                media_file_name: file.name,
                media_url: '' // Clear URL input when a file is uploaded
            }));
        };
        reader.readAsDataURL(file);
    };

    const handleClearFile = () => {
        setFormData(prev => ({
            ...prev,
            media_file: '',
            media_file_name: ''
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsConfirming(true);
    };

    const confirmSubmit = async () => {
        setStatus({ type: '', message: '', errors: {} });
        
        try {
            const response = await fetch('http://localhost/backend/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.status === 201) {
                setStatus({ type: 'success', message: 'Appointment successfully booked!', errors: {} });
                setSubmittedData(data);
                setIsConfirming(false);
                setFormData({
                    name: '', email: '', service_date: '', vehicle_model: '',
                    custom_vehicle_model: '', service_type: '', custom_service_type: '', media_url: '', media_file: '', media_file_name: '', notes: ''
                });
            } else if (response.status === 400) {
                setStatus({ type: 'error', message: 'Please correct the highlighted errors.', errors: data });
                setIsConfirming(false);
            } else {
                setStatus({ type: 'error', message: 'Something went wrong. Please try again.', errors: {} });
                setIsConfirming(false);
            }
        } catch (error) {
            setStatus({ type: 'error', message: 'Network error. Please make sure the server is running.', errors: {} });
            setIsConfirming(false);
        }
    };

    if (status.type === 'success' && submittedData) {
        return (
            <div className="editorial-wrapper fade-in">
                <div className="editorial-hero">
                    <BrandLogo />
                    <h1>APEX</h1>
                    <p className="hero-subtitle">Booking Confirmed</p>
                    <div className="hero-accent-line"></div>
                </div>
                <div className="editorial-form-section">
                    <div className="success-content">
                        <h2>Thank you, {submittedData.name}.</h2>
                        <p className="success-message">Your vehicle is in expert hands. We have received your service request.</p>
                        
                        <div className="summary-details">
                            <div className="detail-row"><span>Date</span> <span>{submittedData.service_date}</span></div>
                            <div className="detail-row"><span>Vehicle</span> <span>{submittedData.vehicle_model === 'Other' ? submittedData.custom_vehicle_model : submittedData.vehicle_model}</span></div>
                            <div className="detail-row"><span>Service</span> <span>{submittedData.service_type === 'Other' ? submittedData.custom_service_type : submittedData.service_type}</span></div>
                        </div>

                        <button className="btn-primary" onClick={() => setStatus({ type: '', message: '', errors: {} })}>
                            New Booking
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="editorial-wrapper fade-in">
            <div className="editorial-hero">
                <BrandLogo />
                <h1>APEX</h1>
                <p className="hero-subtitle">Precision Service. Quality Repair.</p>
                <div className="hero-accent-line"></div>
            </div>
            
            <div className="editorial-form-section">
                {isConfirming ? (
                    <div className="confirmation-content fade-in" style={{ maxWidth: '600px' }}>
                        <div className="form-header-minimal">
                            <h2>Review Details</h2>
                        </div>
                        <p className="success-message" style={{ marginBottom: '2rem' }}>Please confirm your booking information before submitting.</p>
                        
                        <div className="summary-details" style={{ borderTop: 'none', paddingTop: 0 }}>
                            <div className="detail-row"><span>Name</span> <span>{formData.name}</span></div>
                            <div className="detail-row"><span>Email</span> <span>{formData.email}</span></div>
                            <div className="detail-row"><span>Date</span> <span>{formData.service_date}</span></div>
                            <div className="detail-row"><span>Vehicle</span> <span>{formData.vehicle_model === 'Other' ? formData.custom_vehicle_model : formData.vehicle_model}</span></div>
                            <div className="detail-row"><span>Service</span> <span>{formData.service_type === 'Other' ? formData.custom_service_type : formData.service_type}</span></div>
                            {(formData.media_url || formData.media_file_name) && (
                                <div className="detail-row"><span>Media</span> <span>{formData.media_file_name ? formData.media_file_name : 'Attached URL'}</span></div>
                            )}
                            {formData.notes && (
                                <div className="detail-row"><span>Notes</span> <span>{formData.notes}</span></div>
                            )}
                        </div>

                        <div style={{ display: 'flex', gap: '1rem', marginTop: '2rem' }}>
                            <button className="btn-primary" onClick={() => setIsConfirming(false)} style={{ background: 'transparent', border: '1px solid var(--border-subtle)', color: 'var(--text-main)', marginTop: 0 }}>
                                Edit Info
                            </button>
                            <button className="btn-primary" onClick={confirmSubmit} style={{ marginTop: 0 }}>
                                Submit
                            </button>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="form-header-minimal">
                            <h2>Schedule Service</h2>
                        </div>
                        
                        {status.message && status.type === 'error' && (
                            <div className="alert alert-error">{status.message}</div>
                        )}

                        <form onSubmit={handleSubmit} className="appointment-form">
                    <div className="form-row">
                        <div className="form-group floating-group">
                            <input type="text" id="name" name="name" value={formData.name} onChange={handleChange} className={status.errors.name ? 'input-error' : ''} required placeholder=" " />
                            <label htmlFor="name">Full Name <span className="required">*</span></label>
                            {status.errors.name && <span className="error-text">{status.errors.name}</span>}
                        </div>

                        <div className="form-group floating-group">
                            <input type="email" id="email" name="email" value={formData.email} onChange={handleChange} className={status.errors.email ? 'input-error' : ''} required placeholder=" " />
                            <label htmlFor="email">Email Address <span className="required">*</span></label>
                            {status.errors.email && <span className="error-text">{status.errors.email}</span>}
                        </div>
                    </div>

                    <div className="form-group">
                        <label htmlFor="service_date">Preferred Date <span className="required">*</span></label>
                        <input type="date" id="service_date" name="service_date" value={formData.service_date} onChange={handleChange} className={status.errors.service_date ? 'input-error' : ''} required />
                        {status.errors.service_date && <span className="error-text">{status.errors.service_date}</span>}
                    </div>

                    <div className="form-group">
                        <label>Vehicle Model <span className="required">*</span></label>
                        <div className="radio-group-horizontal">
                            {['Mirage G4', 'Xpander', 'Montero Sport', 'Triton', 'Other'].map(model => (
                                <label key={model} className="radio-label-minimal">
                                    <input type="radio" name="vehicle_model" value={model} checked={formData.vehicle_model === model} onChange={handleChange} required />
                                    <span className="radio-text">{model}</span>
                                </label>
                            ))}
                        </div>
                        {status.errors.vehicle_model && <span className="error-text">{status.errors.vehicle_model}</span>}
                    </div>

                    {formData.vehicle_model === 'Other' && (
                        <div className="form-group floating-group fade-in">
                            <input type="text" id="custom_vehicle_model" name="custom_vehicle_model" value={formData.custom_vehicle_model} onChange={handleChange} className={status.errors.custom_vehicle_model ? 'input-error' : ''} required placeholder=" " />
                            <label htmlFor="custom_vehicle_model">Specify Vehicle <span className="required">*</span></label>
                        </div>
                    )}

                    <div className="form-group">
                        <label>Service Type <span className="required">*</span></label>
                        <div className="radio-group-horizontal">
                            {['Oil Change', 'Diagnostic', 'Repair', 'Other'].map(service => (
                                <label key={service} className="radio-label-minimal">
                                    <input type="radio" name="service_type" value={service} checked={formData.service_type === service} onChange={handleChange} required />
                                    <span className="radio-text">{service}</span>
                                </label>
                            ))}
                        </div>
                        {status.errors.service_type && <span className="error-text">{status.errors.service_type}</span>}
                    </div>

                    {formData.service_type === 'Other' && (
                        <div className="form-group floating-group fade-in">
                            <input type="text" id="custom_service_type" name="custom_service_type" value={formData.custom_service_type} onChange={handleChange} className={status.errors.custom_service_type ? 'input-error' : ''} required placeholder=" " />
                            <label htmlFor="custom_service_type">Specify Service <span className="required">*</span></label>
                        </div>
                    )}

                    <div className="form-group">
                        <label htmlFor="media_url">Media Attachment</label>
                        <div className="media-input-group">
                            <input type="url" id="media_url" name="media_url" value={formData.media_url} onChange={handleChange} placeholder="https://..." className={status.errors.media_url ? 'input-error' : ''} disabled={!!formData.media_file_name} />
                            <span className="media-separator">or</span>
                            <label className="btn-file-upload-minimal">
                                <input type="file" accept="image/*,video/*" onChange={handleFileChange} style={{ display: 'none' }} />
                                {formData.media_file_name ? 'Change' : 'Upload'}
                            </label>
                        </div>
                        {formData.media_file_name && (
                            <div className="file-badge">
                                <span className="file-name">{formData.media_file_name}</span>
                                <button type="button" onClick={handleClearFile} className="file-remove">✕</button>
                            </div>
                        )}
                    </div>

                    <div className="form-group floating-group">
                        <textarea id="notes" name="notes" rows="2" value={formData.notes} onChange={handleChange} className={status.errors.notes ? 'input-error' : ''} placeholder=" "></textarea>
                        <label htmlFor="notes">Notes</label>
                    </div>

                    <button type="submit" className="btn-primary">Submit</button>
                </form>
                </>
                )}
            </div>
        </div>
    );
};

export default AppointmentForm;
